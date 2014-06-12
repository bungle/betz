<?php
namespace db\chat {
    function post($user, $message) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT INTO chat (time, user, message) VALUES (:time, :user, :message)');
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->bindValue(':user', $user, SQLITE3_TEXT);
        $stm->bindValue(':message', $message, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function latest($limit, &$last) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM chat ORDER BY id DESC LIMIT :limit');
        $stm->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $res = $stm->execute();
        $messages = array();
        $first = true;
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if ($first) {
                $last = $row['id'];
                $first = false;
            }
            $messages[] = $row;
        }
        $res->finalize();
        $stm->close();
        return array_reverse($messages);
    }
    function backlog() {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM chat ORDER BY id ASC');
        $res = $stm->execute();
        $messages = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $messages[] = $row;
        $res->finalize();
        $stm->close();
        return $messages;
    }    
    function poll(&$last) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM chat WHERE id > :id ORDER BY id');
        $stm->bindValue(':id', $last, SQLITE3_INTEGER);
        $res = $stm->execute();
        $messages = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $last = $row['id'];
            $messages[] = $row;
        }
        $res->finalize();
        $stm->close();
        return $messages;
    }
}