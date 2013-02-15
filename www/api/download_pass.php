<?php
require_once "src/models/user.php";
require_once "src/passbook.php";
require_once "src/util.php";

$has_user_id = array_key_exists("user_id", $_GET);
$has_biscuit_id = array_key_exists("biscuit_id", $_GET);

if (!$has_user_id && !$has_biscuit_id) {
    echo("ERROR: No user or biscuit was specified.");
} else {
    if ($has_user_id) {
        $user_id = intval($_GET["user_id"]);
        $user = new User(array("id" => $user_id));
        $pass_filename = $user->pass_filename;

        if (!$pass_filename) {
            echo("ERROR: No pass exists for user " . $user_id);
        } else {
            $passes_path = Util::config_get_key("passes_path");
            $pass_filepath = Util::path_join(array($passes_path, $pass_filename));

            if (!file_exists($pass_filepath)) {
                echo("ERROR: Pass file does not exist.");
            } else {
                header("Content-Type: application/vnd.apple.pkpass");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($pass_filepath));
                header("Content-Disposition: attachment");
                readfile($pass_filepath);
            }
        }
    }
}

?>
