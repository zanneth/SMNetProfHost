<?php

require_once "src/models/user.php";
require_once "src/error.php";
require_once "src/util.php";

$required_keys = array("username");
$valid_request = Util::verify_required_keys($_POST, $required_keys);
if (!$valid_request) {
    $url = sprintf("../add_user.php?error=%d", Error::MISSING_INFORMATION_ERROR);
    Util::redirect($url);
}

$primary_key = $_POST["id"];
$username = $_POST["username"];
$display_name = array_key_exists("display_name", $_POST) ? $_POST["display_name"] : NULL;

$user = NULL;
if ($primary_key) {
    $user = new User(array("id" => $primary_key));
} else {
    $user = new User();
}

$user->username = $username;
if ($display_name) {
    $user->display_name = $display_name;
}
$success = $user->save();

if ($success) {
    Util::redirect("../user_management.php");
} else {
    $url = sprintf("../add_user.php?error=%d", Error::DATABASE_ERROR);
    Util::redirect($url);
}

?>
