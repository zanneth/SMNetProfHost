<?php

require_once "src/models/biscuit.php";
require_once "src/models/user.php";
require_once "src/util.php";

$valid = Util::verify_required_keys($_POST, array("user_id"));
if (!$valid) {
    echo("Error: no biscuit owner id provided.");
} else {
    $user_id = intval($_POST["user_id"]);
    $success = false;
    try {
        $success = Biscuit::create_biscuit($user_id);
    } catch (Exception $e) {
        echo(sprintf("Error creating biscuit for user id %d", $user_id));
    }

    if ($success) {
        Util::redirect(sprintf("../view_user.php?id=%d", $user_id));
    }
}

?>
