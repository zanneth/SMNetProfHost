<?php
/*
 * user.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/11/2013
 */

require_once "src/models/base.php";
require_once "src/database.php";

define("ACTIVE_USER_SESSION_KEY", "com.magahern.smnetprofhost.activeuser");

class User extends ModelBase {
    public $table_name = "users";

    public $username;
    public $password_hash;
    public $display_name;
    public $highscore_name;
    public $weight;
    public $num_credits;

    /* Managing Active User */

    static function get_active_user()
    {
        session_start();
        if (isset($_SESSION[ACTIVE_USER_SESSION_KEY])) {
            $user = $_SESSION[ACTIVE_USER_SESSION_KEY];
            return $user;
        } else {
            return NULL;
        }
    }

    static function set_active_user(User $user)
    {
        session_start();
        $_SESSION[ACTIVE_USER_SESSION_KEY] = $user;
    }

    static function clear_active_user()
    {
        session_start();
        unset($_SESSION[ACTIVE_USER_SESSION_KEY]);
        session_destroy();
    }

    /* Password Management */

    function set_password($password)
    {
        $hsh = $this->_hash_password($password);
        $this->_password_hash = $hsh;
    }

    function check_password($password)
    {
        $hsh = $this->_hash_password($password);
        return $hsh == $this->_password_hash;
    }

    /* Aggregate Constructors */

    static function fetch_user_for_username($username)
    {
        $query = "SELECT `id` FROM `users` WHERE `username` = :username";
        $params = array(":username" => $username);
        $db = new SMNetProfDatabase();
        $results = $db->execute_query($query, $params);

        $user = NULL;
        if (count($results) > 0) {
            $user = new User($results[0]);
        }

        return $user;
    }

    /* Private Functions */

    private function _hash_password($password)
    {
        $hsh = hash("sha256", $password);
        return $hsh;
    }
}

?>
