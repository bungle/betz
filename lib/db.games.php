<?php
namespace db\games {
    function find($id) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM view_games WHERE id = :id');
        $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        return $row;
    }
    function bets($id) {
        $bets = array();
        $db = \db\connect();
        $stm = $db->prepare('SELECT b.* FROM gamebets b INNER JOIN users u ON b.user = u.username WHERE game = :id AND u.active = 1 ORDER BY LOWER(user)');
        $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stm->execute();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $bets[] = $row;
        $res->finalize();
        return $bets;
    }
    function all() {
        $games = array();
        $db = \db\connect();
        $res = $db->query('SELECT * FROM view_games ORDER BY time');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $games[] = $row;
        $res->finalize();
        return $games;
    }
    function add($time, $home, $road, $draw) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT INTO games (home, road, draw, time) VALUES (:home, :road, :draw, :time)');
        $stm->bindValue(':home', $home, SQLITE3_TEXT);
        $stm->bindValue(':road', $road, SQLITE3_TEXT);
        $stm->bindValue(':draw', $draw ? 1 : 0, SQLITE3_INTEGER);
        $stm->bindValue(':time', $time, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function start() {
        $db = \db\connect();
        $start = $db->querySingle('SELECT MIN(time) FROM games', false);
        return $start;
    }
    function started($id) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT id FROM games WHERE id = :id AND time <= :time');
        $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
    function played($limit = null) {
        $sql =<<< 'SQL'
    SELECT * FROM
        view_games
    WHERE
        time < :time
    ORDER BY
        time DESC, id DESC
SQL;
        if ($limit != null) {
            $sql .= ' LIMIT ' . $limit;
        }
        $db = \db\connect();
        $stm = $db->prepare($sql);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $games = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $games[] = $row;
        $res->finalize();
        $stm->close();
        return $games;
    }    
    function score($id, $home_goals, $road_goals, $home_goals_total, $road_goals_total) {
        $db = \db\connect();
        if (defined('GAME_POINTS_STRATEGY') && GAME_POINTS_STRATEGY === 'exp') {
            $percents = percents($id);
            if ($home_goals === $road_goals) {
                $points = exp(1.0 - (float) $percents['draw_percent'] / 100.0);
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = :points WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', 'X', SQLITE3_TEXT);
                $stm->bindValue(':points', $points, SQLITE3_FLOAT);
                $stm->execute();
                $stm->close();
            } elseif ($home_goals > $road_goals) {
                $points = exp(1.0 - (float) $percents['home_percent'] / 100.0);
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = :points WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', '1', SQLITE3_TEXT);
                $stm->bindValue(':points', $points, SQLITE3_FLOAT);
                $stm->execute();
                $stm->close();
            } else {
                $points = exp(1.0 - (float) $percents['road_percent'] / 100.0);
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = :points WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', '2', SQLITE3_TEXT);
                $stm->bindValue(':points', $points, SQLITE3_FLOAT);
                $stm->execute();
                $stm->close();
            }            
        } else {
            if ($home_goals === $road_goals) {
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = (200.0 - draw_percent) / 100.0 WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', 'X', SQLITE3_TEXT);
                $stm->execute();
                $stm->close();
            } elseif ($home_goals > $road_goals) {
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = (200.0 - home_percent) / 100.0 WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', '1', SQLITE3_TEXT);
                $stm->execute();
                $stm->close();
            } else {
                $stm = $db->prepare('UPDATE games SET home_goals = :home, home_goals_total = :home_goals_total, road_goals = :road, road_goals_total = :road_goals_total, score = :score, points = (200.0 - road_percent) / 100.0 WHERE id = :id');
                $stm->bindValue(':id', $id, SQLITE3_INTEGER);
                $stm->bindValue(':home', $home_goals, SQLITE3_INTEGER);
                $stm->bindValue(':home_goals_total', $home_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':road', $road_goals, SQLITE3_INTEGER);
                $stm->bindValue(':road_goals_total', $road_goals_total, SQLITE3_INTEGER);
                $stm->bindValue(':score', '2', SQLITE3_TEXT);
                $stm->execute();
                $stm->close();
            }            
        }

    }
    function percents($id) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT home_percent, draw_percent, road_percent FROM games WHERE id = :id');
        $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        return $row;
    }
}