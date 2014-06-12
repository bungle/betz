<?php
namespace db\users {
    function update($username, $active, $paid, $admin) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE users SET active = :active, paid = :paid, admin = :admin WHERE username = :username');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':paid', $paid ? 1 : 0, SQLITE3_INTEGER);
        $stm->bindValue(':active', $active ? 1 : 0, SQLITE3_INTEGER);
        $stm->bindValue(':admin', $admin ? 1 : 0, SQLITE3_INTEGER);
        $stm->execute();
        $stm->close();
    }
    function all() {
        $users = array();
        $db = \db\connect();
        $res = $db->query('SELECT * FROM users ORDER BY LOWER(username) ASC');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $users[] = $row;
        $res->finalize();
        return $users;
    }
    function login($username, $password) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT username, password FROM users WHERE LOWER(username) = LOWER(:username) AND active = :active');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        if ($row === false || \password\check($password, $row[1]) === false) return false;
        return $row[0];
    }
    function authenticate($username) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(:username) AND active = :active');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();
        $stm->close();
        return $row;
    }
    function register($username, $password, $email, $admin = false) {
        if (username_taken($username) || email_taken($email))  return 0;
        $db = \db\connect();
        $stm = $db->prepare('INSERT OR IGNORE INTO users (username, password, email, active, admin) VALUES (:username, :password, :email, :active, :admin)');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':password', $password, SQLITE3_TEXT);
        $stm->bindValue(':email', $email, SQLITE3_TEXT);
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $stm->bindValue(':admin', $admin ? 1 : 0, SQLITE3_INTEGER);
        $stm->execute();
        $changes = $db->changes();
        $stm->close();
        return $changes;
    }
    function claim($username, $claim, $email, $admin = false) {
        if (username_taken($username) || email_taken($email))  return 0;
        $db = \db\connect();
        $stm = $db->prepare('INSERT OR IGNORE INTO users (username, claim, email, active, admin) VALUES (:username, :claim, :email, :active, :admin)');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':claim', $claim, SQLITE3_TEXT);
        $stm->bindValue(':email', $email, SQLITE3_TEXT);
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $stm->bindValue(':admin', $admin ? 1 : 0, SQLITE3_INTEGER);
        $stm->execute();
        $changes = $db->changes();
        $stm->close();
        return $changes;
    }
    function claimed($claim, $email) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT username, claim FROM users WHERE email = :email');
        $stm->bindValue(':email', $email, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        if ($row === false || \password\check($claim, $row[1]) === false) return false;
        return $row[0];
    }
    function username_taken($username) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT 1 FROM users WHERE LOWER(username) = LOWER(:username)');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
    function email_taken($email) {
        $db = \db\connect();
        $stm = $db->prepare('SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email)');
        $stm->bindValue(':email', $email, SQLITE3_TEXT);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return $row !== false;
    }
    function remember($username, $key, $expire) {
        $db = \db\connect();
        $stm = $db->prepare('INSERT OR REPLACE INTO remember (user, key, expire) VALUES (:user, :key, :expire)');
        $stm->bindValue(':user', $username, SQLITE3_TEXT);
        $stm->bindValue(':key', $key, SQLITE3_TEXT);
        $stm->bindValue(':expire', $expire, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function forget($username, $key) {
        $db = \db\connect();
        $stm = $db->prepare('DELETE FROM remember WHERE user = :user AND key = :key)');
        $stm->bindValue(':user', $username, SQLITE3_TEXT);
        $stm->bindValue(':key', $key, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
    }
    function remembered($username, $key) {
        $db = \db\connect();
        $stm = $db->prepare('DELETE FROM remember WHERE user = :user AND key = :key AND expire > :expire');
        $stm->bindValue(':user', $username, SQLITE3_TEXT);
        $stm->bindValue(':key', $key, SQLITE3_TEXT);
        $stm->bindValue(':expire', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->execute();
        $changes = $db->changes();
        $stm->close();
        if ($changes > 0) return authenticate($username);
        return false;
    }
    function paid() {
        $db = \db\connect();
        $stm = $db->prepare('SELECT COUNT(paid) FROM users WHERE paid = :paid');
        $stm->bindValue(':paid', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $res->finalize();
        $stm->close();
        return ($row) ? $row[0] : 0;
    }
    function visited($username, $page) {
        $db = \db\connect();
        $stm = $db->prepare('UPDATE users SET visited_time = :time, visited_page = :page WHERE username = :username');
        $stm->bindValue(':username', $username, SQLITE3_TEXT);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->bindValue(':page', $page, SQLITE3_TEXT);
        $stm->execute();
        $stm->close();
        $stm = $db->prepare('SELECT username, visited_time, visited_page FROM users WHERE visited_time > :time ORDER BY visited_time DESC');
        $stm->bindValue(':time', date_format(date_create('5 minutes ago'), DATE_SQLITE), SQLITE3_TEXT);
        $res = $stm->execute();
        $users = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $users[] = $row;
        $res->finalize();
        $stm->close();
        return $users;
    }
    function teams_not_betted() {
        $db = \db\connect();
        $stm = $db->prepare('SELECT email FROM users WHERE active = :active AND (winner IS NULL OR second IS NULL OR third IS NULL) ORDER BY email');
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $emails = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $emails[] = $row;
        $res->finalize();
        $stm->close();
        return $emails;
    }
    function games_not_betted() {
        $sql = <<<'SQL'
            SELECT email FROM users WHERE active = :active AND username NOT IN (
                SELECT user FROM gamebets WHERE game IN (
                    SELECT id FROM games WHERE time = (SELECT MIN(time) FROM games WHERE time > :time)
                )
            ) ORDER BY email
SQL;
        $db = \db\connect();
        $stm = $db->prepare($sql);
        $stm->bindValue(':time', date_format(date_create(), DATE_SQLITE), SQLITE3_TEXT);
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $emails = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $emails[] = $row;
        $res->finalize();
        $stm->close();
        return $emails;        
    }
    function emails() {
        $db = \db\connect();
        $stm = $db->prepare('SELECT email FROM users WHERE active = :active');
        $stm->bindValue(':active', 1, SQLITE3_INTEGER);
        $res = $stm->execute();
        $emails = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $emails[] = $row;
        $res->finalize();
        $stm->close();
        return $emails;
    }    
    
}