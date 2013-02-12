<?php
/*
 * upload_stats.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/11/2013
 */

require_once "src/models/bisquit.php";
require_once "src/util.php";

function upload($id, $xml_file)
{
    $bisquit = null;
    if (Bisquit::exists($id)) {
        $bisquit = new Bisquit($id);
    } else {
        $bisquit = Bisquit::create_bisquit();
    }

    $stats_path = Util::path_join(array($bisquit->get_bisquit_path(), STATS_FILENAME));
    $result = move_uploaded_file($xml_file["tmp_name"], $stats_path);
    if (!$result) {
        error_log("Could not upload stats file to " . $stats_path);
    }
}

if (isset($_POST['id']) && isset($_FILES["xmlfile"])) {
    upload($_POST['id'], $_FILES["xmlfile"]);
}

?>
