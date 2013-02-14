<?php

require_once "src/models/biscuit.php";
require_once "src/util.php";

function upload($id, $xml_file)
{
    // TODO
    $result = move_uploaded_file($xml_file["tmp_name"], $stats_path);
    if (!$result) {
        error_log("Could not upload stats file to " . $stats_path);
    }
}

if (isset($_POST['id']) && isset($_FILES["xmlfile"])) {
    upload($_POST['id'], $_FILES["xmlfile"]);
}

?>
