<?php
/*
 * database.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/10/2013
 */

require_once "util.php";

class SMNetProfDatabase {
    private $_pdo_handle;

    public function __construct()
    {
        $db_path = Util::config_get_key("database_path");
        $pdo_dsn = sprintf("sqlite:%s", $db_path);

        $_pdo_handle = new PDO($pdo_dsn);
        if (!$_pdo_handle) {
            throw new Exception("Could not open SQLite database.");
        }
    }

    public function execute_update($update_sql)
    {
        return $_pdo_handle->exec($update_sql);
    }

    public function execute_query($query_sql)
    {
        return $_pdo_handle->query($query_sql);
    }

    public function fetch_row($table_name, $primary_key)
    {
        $row = NULL;
        $query = sprintf("SELECT * FROM %s WHERE id = %d LIMIT 1", $table_name, $primary_key);
        $rows = execute_query($query);
        if (count($rows) > 0) {
            $row = $rows[0];
        }
        return $row;
    }
}

?>
