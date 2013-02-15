<?php

require_once "src/models/user.php";
require_once "src/passbook.php";
require_once "src/util.php";

$has_user_id = array_key_exists("user_id", $_POST);
$has_biscuit_id = array_key_exists("biscuit_id", $_POST);

if (!$has_user_id && !$has_biscuit_id) {
    echo("ERROR: No user or biscuit was specified.");
} else {
    if ($has_user_id) {
        $user_id = intval($_POST["user_id"]);
        $user = new User(array("id" => $user_id));

        if ($user->pass_filename) {
            echo("ERROR: User already has a pass stored at " . $user->pass_filename);
        } else {
            $user_pass = new UserPassbookPass($user);

            $pass_filename = sprintf("user-%d-%s.pkpass", $user->primary_key, $user->uuid);
            $passes_path = Util::config_get_key("passes_path");
            $pass_filepath = Util::path_join(array($passes_path, $pass_filename));
            $success = $user_pass->save_pass($pass_filepath);
            if ($success) {
                $user->pass_filename = $pass_filename;
                $user->save();
                error_log("Successfully created new Passbook pass for user " . $user_id);

                Util::redirect(sprintf("../view_user.php?id=%d", $user_id));
            } else {
                error_log("Failed to create pass for user " . $user_id);
            }
        }
    } else if ($has_biscuit_id) {
        // TODO
    }
}

?>
