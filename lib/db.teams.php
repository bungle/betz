<?php
namespace db\teams {
    function all($idropped = true) {
        $teams = array();
        $db = \db\connect();
        if ($idropped) {
            $res = $db->query('SELECT * FROM teams ORDER BY LOWER(name)');
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) $teams[] = $row;
            $res->finalize();
        } else {
            $res = $db->query('SELECT * FROM teams WHERE ranking IS NULL OR ranking < 0 ORDER BY LOWER(name)');
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) $teams[] = $row;
            $res->finalize();
        }
        return $teams;
    }
    function add($name, $abbr) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT INTO teams (name, abbr) VALUES (:name, :abbr);');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $stm->bindValue(':abbr', $abbr, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }    
    function exists($name) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT 1 FROM teams WHERE name = :name');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
    function ranking($team, $ranking) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE teams SET ranking = :ranking WHERE name = :team');
        $stm->bindValue(':ranking', $ranking, SQLITE3_INTEGER);
        $stm->bindValue(':team', $team, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
}