<?php
namespace db\teams {
    function all() {
        $teams = array();
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $res = $db->query('SELECT * FROM teams ORDER BY LOWER(name)');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $teams[] = $row;
        $res->finalize();
        $db->close();
        return $teams;
    }
    function add($name, $abbr) {
        $games = array();
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READWRITE);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('INSERT INTO teams (name, abbr) VALUES (:name, :abbr);');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $stm->bindValue(':abbr', $abbr, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
        $db->close();
    }    
    function exists($name) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('SELECT 1 FROM teams WHERE name = :name');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        $db->close();
        return $row !== false;
    }
    function ranking($team, $ranking) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READWRITE);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('UPDATE teams SET ranking = :ranking WHERE name = :team');
        $stm->bindValue(':ranking', $ranking, SQLITE3_INTEGER);
        $stm->bindValue(':team', $team, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
        $db->close();
    }
}