<?php
/*
 * bisquit.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/10/2013
 */

require_once "database.php";
require_once "util.php";

define("STATS_FILENAME", "stats.xml");
define("EDITABLE_FILENAME", "editable.xml");
define("DEFAULT_BISQUIT_DIRNAME", "default");

class Bisquit {
    public static $table_name = "bisquits";

    public $primary_key;
    public $num_credits;
    public $identifier;

    public function __construct($primary_key)
    {
        $db = new SMNetProfDatabase();
        $row = $db->fetch_row(self::$table_name, $primary_key);
        if (is_null($row)) {
            throw new Exception("Could not fetch row for bisquit with id " . $primary_key);
        }

        $this->primary_key = $primary_key;
        $this->num_credits = $row["num_credits"];
        $this->identifier  = $row["uuid"];
    }

    public static function get_default_bisquit_path()
    {
        $bisquits_path = Util::config_get_key("bisquits_path");
        return Util::path_join(array($bisquits_path, DEFAULT_BISQUIT_DIRNAME));
    }

    public static function copy_bisquit_files($bisquit_path)
    {
        $filenames = array(
            STATS_FILENAME,
            EDITABLE_FILENAME
        );
        $success = true;

        if (!is_dir($bisquit_path)) {
            $success = mkdir($bisquit_path);
            if (!$success) {
                error_log("Could not create new bisquit file at path " . $bisquit_path);
            }
        }

        if ($success) {
            $default_bisquit_path = self::get_default_bisquit_path();
            foreach ($filenames as $bisquit_filename) {
                $orig_path = Util::path_join($default_bisquit_path, $bisquit_filename);
                $dest_path = Util::path_join($bisquit_path, $bisquit_filename);
                $success = copy($orig_path, $dest_path);
                if (!$success) {
                    error_log("Could not copy bisquit file " . $dest_path);
                    break;
                }
            }
        }

        return $success;
    }

    public static function create_bisquit()
    {
        $bisquit = NULL;

        // insert the new row into the database
        $db = new SMNetProfDatabase();
        $new_uuid = uniqid();
        $sql = sprintf("INSERT INTO %s (uuid) VALUES (%s)", self::$table_name, $new_uuid);
        $success = $db->execute_update($sql);
        if (!$success) {
            throw new Exception("Could not create new bisquit.");
        }

        // create the new bisquit directory
        $bisquits_path = Util::config_get_key("bisquits_path");
        $bisquit_path = Util::path_join(array($bisquits_path, $new_uuid));
        $copy_success = Bisquit::copy_bisquit_files($bisquit_path);

        // create the new bisquit object
        if ($copy_success) {
            $new_pk = $db->lastInsertId();
            $bisquit = new Bisquit($new_pk);
        }

        return $bisquit;
    }

    public function save()
    {
        $colnames_values = array(
            "num_credits"   => $this->num_credits,
            "uuid"          => $this->identifier
        );
        $values_expr = "";
        $counter = 0;
        foreach ($colnames_values as $colname => $value) {
            $values_expr .= sprintf("%s = %s", $colname, $value);
            if ($counter < count($colnames_values) - 1) {
                $values_expr .= ',';
            }
            ++$counter;
        }

        $sql = sprintf("UPDATE %s SET %s WHERE id = %d", self::$table_name, $values_expr, $this->primary_key);
        $db = new SMNetProfDatabase();
        return $db->execute_update($sql);
    }
}

?>
