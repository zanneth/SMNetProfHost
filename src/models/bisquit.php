<?php
/*
 * biscuit.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/10/2013
 */

require_once "src/models/base.php";
require_once "src/models/user.php";
require_once "src/database.php";
require_once "src/util.php";

define("STATS_FILENAME", "stats.xml");
define("EDITABLE_FILENAME", "editable.xml");
define("DEFAULT_BISQUIT_DIRNAME", "default");

class Biscuit extends ModelBase {
    public $table_name = "biscuits";

    public $date_created;
    public $uuid;
    public $owner_id;

    public static function get_default_biscuit_path()
    {
        $biscuits_path = Util::config_get_key("biscuits_path");
        return Util::path_join(array($biscuits_path, DEFAULT_BISQUIT_DIRNAME));
    }

    public static function copy_biscuit_files($biscuit_path)
    {
        $filenames = array(
            STATS_FILENAME,
            EDITABLE_FILENAME
        );
        $success = true;

        if (!is_dir($biscuit_path)) {
            $success = mkdir($biscuit_path);
            if (!$success) {
                error_log("Could not create new biscuit file at path " . $biscuit_path);
            }
        }

        if ($success) {
            $default_biscuit_path = self::get_default_biscuit_path();
            foreach ($filenames as $biscuit_filename) {
                $orig_path = Util::path_join(array($default_biscuit_path, $biscuit_filename));
                $dest_path = Util::path_join(array($biscuit_path, $biscuit_filename));
                $success = copy($orig_path, $dest_path);
                if (!$success) {
                    error_log("Could not copy biscuit file " . $dest_path);
                    break;
                }
            }
        }

        return $success;
    }

    public static function biscuit_exists($primary_key)
    {
        $sql = sprintf("SELECT COUNT() FROM %s WHERE id = %d", self::$table_name, $primary_key);
        $db = new SMNetProfDatabase();
        return $db->fetch_row($sql)[0];
    }

    public static function create_biscuit()
    {
        $biscuit = NULL;
        $new_uuid = uniqid();

        // create the new biscuit directory
        $biscuits_path = Util::config_get_key("biscuits_path");
        $biscuit_path = Util::path_join(array($biscuits_path, $new_uuid));
        $copy_success = Biscuit::copy_biscuit_files($biscuit_path);

        // create the new biscuit in the database
        if ($copy_success) {
            $db = new SMNetProfDatabase();
            $biscuit = new Biscuit();
            $bisquit->uuid = $new_uuid;
            $success = $biscuit->save();
            if (!$success) {
                throw new Exception("Could not create new biscuit.");
            }
        }

        return $biscuit;
    }

    public function get_biscuit_path()
    {
        $biscuits_path = Util::config_get_key("biscuits_path");
        return Util::path_join(array($biscuits_path, $this->uuid));
    }

    public function get_owner()
    {
        $owner = NULL;
        if (User::exists($this->owner_id)) {
            $owner = new User(array("id" => $this->owner_id));
        }
        return $owner;
    }
}

?>
