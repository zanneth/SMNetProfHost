<?php
/*
 * database.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/10/2013
 */

require_once "src/util.php";

date_default_timezone_set("America/Los_Angeles");

class SMNetProfDatabase {
    private $_pdo_handle;

    public function __construct()
    {
        $db_hostname = Util::config_get_key("database_hostname");
        $db_name = Util::config_get_key("database_name");
        $db_username = Util::config_get_key("database_username");
        $db_password = Util::config_get_key("database_password");
        $pdo_dsn = sprintf("mysql:host=%s;dbname=mysql", $db_hostname, $db_name);

        $this->_pdo_handle = new PDO($pdo_dsn, $db_username, $db_password);
        if (!$this->_pdo_handle) {
            throw new Exception("Could not open SQLite database.");
        }

        $this->create_schema_if_nonexistent();
    }

    static function get_pdo_type($object)
    {
        $type = gettype($object);
        switch ($type) {
            case 'boolean':
            case 'integer':
                $pdo_type = PDO::PARAM_INT;
                break;
            case 'NULL':
                $pdo_type = PDO::PARAM_NULL;
                break;
            default:
                // Anything else is a string, including floats/decimals
                $pdo_type = PDO::PARAM_STR;
                break;
        }

        return $pdo_type;
    }

    public function create_schema_if_nonexistent()
    {
        // check if the database exists first
        $db_name = Util::config_get_key("database_name");
        $db_exists = count($this->execute_query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'")) > 0;

        if (!$db_exists) {
            // create the new database
            $success = $this->execute_update("CREATE DATABASE IF NOT EXISTS $db_name;");
            if (!$success) {
                throw new Exception("Could not create database $db_name");
            }

            // tell MySQL to use this database.
            $this->execute_update("USE $db_name;");

            // load the schema SQL from a file
            // we have to separate the statements from the string because PHP is stupid.
            $schema_path = Util::path_join(array(PROJECT_ROOT, "config", "schema.sql"));
            $schema_str = file_get_contents($schema_path);
            $statements = explode(";", str_replace("\n", "", $schema_str));

            foreach ($statements as $statement) {
                if (strlen($statement) > 0) {
                    $success = $this->execute_update($statement);
                    if (!$success) {
                        throw new Exception("Could not setup database schema.");
                    }
                }
            }
            
            error_log("Successfully created new SMNetProf database.");
        }
    }

    public function execute_update($update_sql, $params = array(), &$insert_id = NULL)
    {
        $statement = $this->_pdo_handle->prepare($update_sql);
        if (!$statement) {
            error_log("Error preparing statement");
            Util::log_description($this->_pdo_handle->errorInfo());
            return false;
        }

        foreach ($params as $key => $value) {
            $pdo_type = self::get_pdo_type($value);
            $statement->bindValue($key, $value, $pdo_type);
        }

        $success = $statement->execute();
        $insert_id = $this->_pdo_handle->lastInsertId();
        return $success;
    }

    public function execute_query($query_sql, $params = array())
    {
        $statement = $this->_pdo_handle->prepare($query_sql);
        foreach ($params as $key => $value) {
            $pdo_type = self::get_pdo_type($value);
            $statement->bindValue($key, $value, $pdo_type);
        }
        $statement->execute();

        $rows = array();
        while ($row = $statement->fetch()) {
            $rows[] = $row;
        }

        return $rows;
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
