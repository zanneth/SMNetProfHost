<?php

require_once "src/models/biscuit.php";
require_once "src/models/user.php";
require_once "src/database.php";
require_once "src/util.php";

function output_contents($data_str)
{
    header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
    header("Content-Type: text/xml");
    header("Content-Transfer-Encoding: Binary");
    header("Content-Length: " . strlen($data_str));
    header("Content-Disposition: attachment");
    echo($data_str);
}

$valid = Util::verify_required_keys($_GET, array("id", "type"));
if ($valid) {
    $uuid = $_GET["id"];
    $type = $_GET["type"];
    $user = User::fetch_user_for_uuid($uuid);
    if ($user != NULL) {
        if ($type == "editable") {
            $editable_data = $user->generate_editable_xml_str();
            output_contents($editable_data);
        } else if ($type == "stats") {
            $biscuits = $user->get_biscuits();
            if (count($biscuits) > 0) {
                $biscuit = $biscuits[0];
                $biscuit_path = $biscuit->get_biscuit_path();
                $stats_path = Util::path_join(array($biscuit_path, STATS_FILENAME));
                $stats_data = file_get_contents($stats_path);
                output_contents($stats_data);
            }
        }
    } else {
        error_log("Could not find user with uuid " . $uuid);
    }
}

// James's shitty code will probably crash if we return an error message, so
// give an empty response if we have an error.

?>
