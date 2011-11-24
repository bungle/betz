<?php
namespace db\news {
    function all() {
        $news = array();
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $res = $db->query('SELECT * FROM news ORDER BY time DESC');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $news[] = $row;
        $res->finalize();
        $db->close();
        return $news;
    }
    function add($id, $title, $content, $level, $user, $slug) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READWRITE);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        if (!isset($id)) {
            $stm = $db->prepare('INSERT INTO news (time, slug, title, content, level, user) VALUES (:time, :slug, :title, :content, :level, :user)');
        } else {
            $stm = $db->prepare('UPDATE news SET time = :time, slug = :slug, title = :title, content = :content, level = :level, user = :user WHERE id = :id');
            $stm->bindValue(':id', $id, SQLITE3_INTEGER);
        }
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stm->bindValue(':user', $user, SQLITE3_TEXT);
        $stm->bindValue(':title', $title, SQLITE3_TEXT);
        $stm->bindValue(':content', $content, SQLITE3_TEXT);
        $stm->bindValue(':level', $level, SQLITE3_INTEGER);
        $stm->execute();
        $stm->close();
        $db->close();
    }
    function edit($id) {
        $db = new \SQLite3(DATABASE, SQLITE3_OPEN_READONLY);
        if (method_exists($db, 'busyTimeout')) $db->busyTimeout(10000);
        $stm = $db->prepare('SELECT * FROM news WHERE id = :id');
        $stm->bindValue('id', $id, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        $db->close();
        return $row;
    }
}