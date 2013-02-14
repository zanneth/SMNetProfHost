<?php
/*
 * passbook.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/13/2013
 *
 * PassKey password: OMHDGC1jh7UjcP
 */

class PassbookPass {
    public $pass_type_identifier;
    public $serial_number;
    public $format_version;
    public $team_identifier;
    public $organization_name;
    public $description;

    public $icon_path;
    public $icon_highres_path;
    public $logo_path;
    public $logo_highres_path;
    public $thumbnail_path;
    public $thumbnail_highres_path;

    public $background_color;   // array of three integers (0..255)
    public $foreground_color;   // array of three integers (0..255)
    public $locations; // dictionaries in form of "latitude" => [float], "longitude" => [float]

    public function __construct()
    {
        $this->format_version = 1;
        $this->description = "Passbook Pass";
    }

    public function generate_pass_json()
    {

    }

    public function save_pass($path)
    {
        // TODO
    }
}

?>

