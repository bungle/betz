<?php
namespace db\news {
    function all() {
        $news = array();
        $db = \db\connect();
        $res = $db->query('SELECT * FROM news ORDER BY time DESC');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $news[] = $row;
        $res->finalize();
        return $news;
    }
    function add($id, $title, $content, $level, $user, $slug) {
        $db = \db\connect();
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
    }
    function edit($id) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM news WHERE id = :id');
        $stm->bindValue('id', $id, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        return $row;
    }
}