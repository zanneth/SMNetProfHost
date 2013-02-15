<?php

require_once "src/models/biscuit.php";
require_once "src/models/user.php";
require_once "src/util.php";

function upload($uuid, $xml_file)
{
    $success = false;
    $user = User::fetch_user_for_uuid($uuid);
    if ($user) {
        $biscuits = $user->get_biscuits();
        if (count($biscuits) > 0) {
            $biscuit = $biscuits[0];
            $biscuit_path = $biscuit->get_biscuit_path();
            $stats_path = Util::path_join(array($biscuit_path, STATS_FILENAME));
            $success = move_uploaded_file($xml_file["tmp_name"], $stats_path);
        }
    }

    if (!$success) {
        error_log("Could not upload stats file to " . $stats_path);
    }
}

if (isset($_POST['id']) && isset($_FILES["xmlfile"])) {
    upload($_POST['id'], $_FILES["xmlfile"]);
}

?>
