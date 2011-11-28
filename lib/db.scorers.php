<?php
namespace db\scorers {
    function all() {
        $db = \db\connect();
        $scorers = array();
        $res = $db->query('SELECT s.name AS name, s.goals AS goals, t.name AS team, t.abbr AS team_abbr FROM scorers s INNER JOIN teams t ON s.team = t.name ORDER BY goals DESC, team, name');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $scorers[] = $row;
        $res->finalize();
        return $scorers;
    }
    function users() {
        $db = \db\connect();
        $scorers = array();
        $res = $db->query('SELECT DISTINCT u.scorer AS scorer_betted, s.name AS scorer, t.name AS team, t.abbr AS team_abbr FROM users u LEFT OUTER JOIN scorermap m ON u.scorer = m.betted LEFT OUTER JOIN scorers s ON m.scorer = s.name LEFT OUTER JOIN teams t ON s.team = t.name WHERE LENGTH(u.scorer) > 2 ORDER BY LOWER(u.scorer)');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $scorers[] = $row;
        $res->finalize();
        return $scorers;
    }
    function add($name, $team, $goals) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT OR REPLACE INTO scorers (name, team, goals) VALUES (:name, :team, :goals)');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $stm->bindValue(':team', $team, SQLITE3_TEXT);
        $stm->bindValue(':goals', $goals, SQLITE3_INTEGER);
        $stm->execute();
        $changes = $db->changes();
        return $changes;
    }
    function goals($name, $goals) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE scorers SET goals = :goals WHERE name = :name');
        $stm->bindValue(':name', $name, SQLITE3_TEXT);
        $stm->bindValue(':goals', $goals, SQLITE3_INTEGER);
        $stm->execute();
        $stm->close();
    }
    function map($scorer, $betted) {
        $db = \db\connect();
        $stm = $db->prepare('DELETE FROM scorermap WHERE betted = :betted');
        $stm->bindValue(':betted', $betted, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
        $stm = $db->prepare('INSERT INTO scorermap (scorer, betted) VALUES (:scorer, :betted)');
        $stm->bindValue(':scorer', $scorer, SQLITE3_TEXT);
        $stm->bindValue(':betted', $betted, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
}