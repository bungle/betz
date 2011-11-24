<?php
namespace db\games {
    function all() {
        $games = array();
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $res = $db->query('SELECT * FROM view_games ORDER BY time');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $games[] = $row;
        $res->finalize();
        $db->close();
        return $games;
    }
    function add($time, $home, $road, $draw) {
        $games = array();
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READWRITE);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('INSERT INTO games (home, road, draw, time) VALUES (:home, :road, :draw, :time)');
        $stm->bindValue(':home', $home, SQLITE3_TEXT);
        $stm->bindValue(':road', $road, SQLITE3_TEXT);
        $stm->bindValue(':draw', $draw ? 1 : 0, SQLITE3_INTEGER);
        $stm->bindValue(':time', $time, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
        $db->close();
    }
    function start() {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $start = $db->querySingle('SELECT MIN(time) FROM games', false);
        $db->close();
        return $start;
    }
    function started($id) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('SELECT id FROM games WHERE id = :id AND time <= :time');
        $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        $db->close();
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
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare($sql);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $games = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $games[] = $row;
        $res->finalize();
        $stm->close();
        $db->close();
        return $games;
    }    
    function score($id, $home_goals, $road_goals, $home_goals_total, $road_goals_total) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READWRITE);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
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
        $db->close();
    }
}