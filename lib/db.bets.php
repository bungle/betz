<?php
namespace db\bets {
    function games($user, $limit = null) {
        $sql =<<< 'SQL'
    SELECT * FROM
        view_games AS g
    LEFT OUTER JOIN
        gamebets AS b
    ON
        g.id = b.game AND b.user = :user
    WHERE
        time > :time
    ORDER BY
        time, id
SQL;
        if ($limit != null) {
            $sql .= ' LIMIT ' . $limit;
        }
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
    function game($game, $user, $score) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT OR REPLACE INTO gamebets (game, user, score) VALUES (:game, :user, :score)');
        $stm->bindValue(':game', $game, SQLITE3_INTEGER);
        $stm->bindValue(':user', $user, SQLITE3_TEXT);
        $stm->bindValue(':score', $score, SQLITE3_TEXT);
        $ok = $stm->execute();
        $stm->close();
        return $ok !== false;
    }
    function winner($user, $team) {
        team($user, $team, 'winner');
    }
    function second($user, $team) {
        team($user, $team, 'second');
    }
    function third($user, $team) {
        team($user, $team, 'third');
    }
    function team($username, $team, $position) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE users SET ' . $position . ' = :team WHERE username = :username');
        $stm->bindValue(':team', $team, SQLITE3_TEXT);
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function scorer($username, $scorer) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE users SET scorer = :scorer WHERE username = :username');
        $stm->bindValue(':scorer', $scorer, SQLITE3_TEXT);
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function single($username) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM view_users WHERE username = :username');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        return $row;
    }
    function started() {
        $db = \db\connect();
        $stm = $db->prepare('SELECT 1 FROM games WHERE time < :time LIMIT 1');
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
    function ended() {
        $db = \db\connect();
        $ended = $db->querySingle('SELECT 1 FROM teams WHERE ranking = 1 LIMIT 1', false);
        return $ended !== false && $ended !== null;
    }
    function notbetted($username) {
        $sql =<<< 'SQL'
        SELECT
            1
        FROM
            games g
        LEFT OUTER JOIN
            gamebets b 
        ON
            g.id = b.game
            AND
            b.user = :user
        WHERE
            g.time > :time
            AND
            b.score IS NULL
        LIMIT 1;
SQL;
        $db = \db\connect();
        $stm = $db->prepare($sql);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->bindValue(':user', $username, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
}