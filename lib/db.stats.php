<?php
namespace db\stats {
    function points() {
        $sql =<<< 'SQL'
        SELECT
            u.username               AS username,
            u.paid                   AS paid,
            COALESCE(SUM(points), 0) AS game_points,
            u.winner                 AS winner,
            u.winner_abbr            AS winner_abbr,
            u.winner_points          AS winner_points,
            u.second                 AS second,
            u.second_abbr            AS second_abbr,
            u.second_points          AS second_points,
            u.third                  AS third,
            u.third_abbr             AS third_abbr,
            u.third_points           AS third_points,
            u.winner_points + u.second_points + u.third_points AS team_points,
            u.scorer                 AS scorer,
            u.scorer_betted          AS scorer_betted,
            u.scorer_points          AS scorer_points,
            COALESCE(ROUND(SUM(points), 2), 0) + u.winner_points + u.second_points + u.third_points + u.scorer_points AS total_points
            FROM
                view_users u
            LEFT OUTER JOIN
                gamebets g
            ON
                u.username = g.user
            GROUP BY
                u.username,
                u.winner,
                u.winner_abbr,
                u.winner_points,
                u.second,
                u.second_abbr,
                u.second_points,
                u.third,
                u.third_abbr,
                u.third_points,
                u.scorer,
                u.scorer_betted,
                u.scorer_points
            HAVING
                u.active = 1
            ORDER BY
                total_points DESC,
                LOWER(username) ASC
SQL;
        $db = \db\connect();
        $res = $db->query($sql);
        $i = 0;
        $j = 0;
        $total = -1;
        $position = 1;
        $rowspan = 1;
        $points = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $points[$i] = $row;
            if ($total != $row['total_points']) {
                $total = $row['total_points'];
                $points[$j]['keyrow'] = true;
                $points[$j]['rowspan'] = $rowspan;
                $points[$i]['position'] = $position;
                $j = $i;
                $rowspan = 1;
                $position++;
            } else {
                $points[$i]['position'] = $position - 1;
                $points[$i]['rowspan'] = $rowspan;
                $points[$i]['keyrow'] = false;
                $rowspan++;
            }
            $i++;
        }
        $points[$j]['rowspan'] = $rowspan;
        $points[$j]['keyrow'] = true;
        $res->finalize();
        return $points;
    }
    function games($user) {
        $sql =<<< 'SQL'
        SELECT
            g.id AS id,
            g.time AS time,
            g.home AS home,
            g.home_abbr AS home_abbr,
            g.home_goals AS home_goals,
            g.home_goals_total AS home_goals_total,
            g.home_percent AS home_percent,
            g.road AS road,
            g.road_abbr AS road_abbr,
            g.road_goals AS road_goals,
            g.road_goals_total AS road_goals_total,
            g.road_percent AS road_percent,
            g.draw AS draw,
            g.draw_percent AS draw_percent,
            g.score AS score,
            g.points AS points,
            b.score AS bet_score,
            b.points AS bet_points
        FROM
            view_games g
        LEFT OUTER JOIN
            gamebets b
        ON
            g.id = b.game AND b.user = :user
        WHERE
            g.time < :time
        ORDER BY
            time DESC
SQL;

        $db = \db\connect();
        $stm = $db->prepare($sql);
        $stm->bindValue(':user', $user, SQLITE3_TEXT);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $games = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $games[] = $row;
        $res->finalize();
        $stm->close();
        return $games;
    }
    function scorers() {
        $sql =<<< 'SQL'
        SELECT
            s.name AS scorer,
            t.name AS team,
            t.abbr AS team_abbr,
            s.goals AS goals,
            GROUP_CONCAT(u.username, ',') AS users
        FROM
            scorers s
        INNER JOIN
            teams t
        ON
            s.team = t.name
        LEFT OUTER JOIN
            scorermap m
        ON
            s.name = m.scorer
        LEFT OUTER JOIN
            users u
        ON
            u.scorer = m.betted
        GROUP BY
            s.name
        HAVING
            goals > 0
        ORDER BY
            goals DESC, team, scorer

SQL;
        $db = \db\connect();
        $res = $db->query($sql);
        $i = 0;
        $j = 0;
        $goals = -1;
        $position = 1;
        $rowspan = 1;
        $scorers = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $row['users'] = explode(',', $row['users']);
            $scorers[$i] = $row;
            if ($goals != $row['goals']) {
                $goals = $row['goals'];
                $scorers[$j]['keyrow'] = true;
                $scorers[$j]['rowspan'] = $rowspan;
                $scorers[$i]['position'] = $position;
                $j = $i;
                $rowspan = 1;
                $position++;
            } else {
                $scorers[$i]['position'] = $position - 1;
                $scorers[$i]['rowspan'] = $rowspan;
                $scorers[$i]['keyrow'] = false;
                $rowspan++;
            }
            $i++;
        }
        if (count($scorers) > 0) {
            $scorers[$j]['rowspan'] = $rowspan;
            $scorers[$j]['keyrow'] = true;
        }
        $res->finalize();
        return $scorers;
    }
    
    function points_history() {
        $sql =<<< 'SQL'
        SELECT
            DISTINCT DATE(time) AS day
        FROM
            games
        WHERE
            score IS NOT NULL
        ORDER BY day;
SQL;
        $days = array();
        $db = \db\connect();
        $res = $db->query($sql);
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $days[] = $row['day'];
        }
        $res->finalize();
        
        $sql =<<< 'SQL'
        SELECT
            b.user AS user,
            SUM(b.points) AS points
        FROM
            games g
        INNER JOIN
            users u
        ON
            b.user = u.username
        INNER JOIN
            gamebets b
        ON
            g.id = b.game
        WHERE
            DATE(g.time) <= :day
            AND
            u.active = 1
        GROUP BY
            user
        ORDER BY
            LOWER(user) ASC
SQL;
        $stm = $db->prepare($sql);
        $data = new \stdClass();
        $data->categories = array();
        $data->series = array();
        $points = array();
        $j = 0;
        
        foreach($days as $day) {
            $points[$j] = array();
            
            $i = 0;
           
            
            $data->categories[] = date_format(date_create($day), 'j.n.');
            $stm->bindValue(':day', $day, SQLITE3_TEXT);
            $res = $stm->execute();
            
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                if (!isset($data->series[$i])) {
                    $data->series[$i] = new \stdClass();
                    $data->series[$i]->name = $row['user'];
                    $data->series[$i]->data = array();
                }
                $point = round($row['points'], 2);
                $points[$j][] = $point;
                $data->series[$i]->data[] = $point;
                $i++;
            }
            
            $res->finalize();
            $p = $points[$j];
            $p = array_unique($p, SORT_NUMERIC);
            rsort($p, SORT_NUMERIC);
            $points[$j] = $p;
            $j++;
        }
 
        $stm->close();

        foreach ($data->series as &$serie) {
            for ($i = 0; $i < count($serie->data); $i++) {
               $serie->data[$i] = (int)array_search($serie->data[$i], $points[$i], true) + 1;
            }
        }
        return $data;
    }
}