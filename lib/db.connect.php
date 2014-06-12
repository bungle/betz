<?php
namespace db {
    function connect() {
        static $sqlite = null;
        if ($sqlite == null) {
            $sqlite = new \SQLite3(DATABASE);
            $sqlite->busyTimeout(10000);
            register_shutdown_function(function($sqlite) { $sqlite->close(); }, $sqlite);
        }
        return $sqlite;
    }
}
