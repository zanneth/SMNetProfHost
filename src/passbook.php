<?php
/*
 * passbook.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/13/2013
 *
 */

require_once "src/models/biscuit.php";
require_once "src/models/user.php";

define("TIMESTAMP_FORMAT", "Y-m-d\TH:i-s");

class PassbookBarcodeFormat {
    const BARCODE_FORMAT_QR     = "PKBarcodeFormatQR";
    const BARCODE_FORMAT_PDF417 = "PKBarcodeFormatPDF417";
    const BARCODE_FORMAT_AZTEC  = "PKBarcodeFormatAztec";
    const BARCODE_FORMAT_TEXT   = "PKBarcodeFormatText";
}

class PassbookBarcode {
    public $message;            // string
    public $format;             // PassbookBarcodeFormat (string)
    public $message_encoding;   // string

    public function __construct($message = "")
    {
        $this->message = $message;
        $this->format = PassbookBarcodeFormat::BARCODE_FORMAT_QR;
        $this->message_encoding = "iso-8859-1";
    }
}

class PassbookField {
    public $key;
    public $value;
    public $label;

    public function __construct($key, $value, $label = "")
    {
        $this->key = $key;
        $this->value = $value;
        $this->label = $label;
    }
}

class PassbookPass {
    // required info
    public $pass_type_identifier;   // string (reverse domain)
    public $serial_number;          // string
    public $format_version;         // integer (default 1)
    public $team_identifier;        // string
    public $organization_name;      // string
    public $description;            // string
    public $passbook_barcode;       // PassbookBarcode

    // cryptography fields (required)
    public $certificate_path;
    public $private_key_path;
    public $wwdr_path;
    public $certificate_password;   // string for the password to the certificate

    // data fields
    public $primary_fields;         // array of PassbookFields
    public $secondary_fields;       // array of PassbookFields
    public $auxiliary_fields;       // array of PassbookFields
    public $back_fields;            // array of PassbookFields

    // asset file paths
    public $icon_path;
    public $icon_highres_path;
    public $logo_path;
    public $logo_highres_path;
    public $thumbnail_path;
    public $thumbnail_highres_path;
    public $strip_path;
    public $strip_highres_path;

    // style options
    public $logo_text;          // string
    public $background_color;   // array of three integers (0..255)
    public $foreground_color;   // array of three integers (0..255)
    public $label_color;        // array of three integers (0..255)

    // additional options
    public $relevant_date;      // string in W3C timestamp form (Y-m-d\TH:i:s\Z)
    public $locations;          // dictionaries in form of "latitude" => [float], "longitude" => [float]

    public function __construct()
    {
        $this->format_version = 1;

        $this->primary_fields = array();
        $this->secondary_fields = array();
        $this->auxiliary_fields = array();
        $this->back_fields = array();
    }

    public function save_pass_json($destination_path)
    {
        $json_dict = array();

        // setup the required info
        $this->_set_if_value($json_dict, "passTypeIdentifier", $this->pass_type_identifier);
        $this->_set_if_value($json_dict, "serialNumber", $this->serial_number);
        $this->_set_if_value($json_dict, "formatVersion", $this->format_version);
        $this->_set_if_value($json_dict, "teamIdentifier", $this->team_identifier);
        $this->_set_if_value($json_dict, "organizationName", $this->organization_name);
        $this->_set_if_value($json_dict, "description", $this->description);

        // setup the barcode
        if (isset($this->passbook_barcode)) {
            $barcode_dict = array();
            $barcode_dict["message"] = $this->passbook_barcode->message;
            $barcode_dict["format"] = $this->passbook_barcode->format;
            $barcode_dict["messageEncoding"] = $this->passbook_barcode->message_encoding;
            $json_dict["barcode"] = $barcode_dict;
        }

        // setup the data fields
        $this->_insert_fields($json_dict, "primaryFields", $this->primary_fields);
        $this->_insert_fields($json_dict, "secondaryFields", $this->secondary_fields);
        $this->_insert_fields($json_dict, "auxiliaryFields", $this->auxiliary_fields);
        $this->_insert_fields($json_dict, "backFields", $this->back_fields);

        // setup style options
        $this->_set_if_value($json_dict, "logoText", $this->logo_text);
        $this->_set_if_color($json_dict, "backgroundColor", $this->background_color);
        $this->_set_if_color($json_dict, "foregroundColor", $this->foreground_color);
        $this->_set_if_color($json_dict, "labelColor", $this->label_color);

        // setup additional options
        $this->_set_if_value($json_dict, "relevantDate", $this->relevant_date);
        if (isset($this->locations)) {
            $json_dict["locations"] = $this->locations;
        }

        // save the json file
        $json_data = json_encode($json_dict);
        return file_put_contents($destination_path, $json_data);
    }

    public function copy_asset_files($destination_path)
    {
        $success = true;

        $success &= $this->_copy_if_set($this->icon_path, $destination_path, "icon.png");
        $success &= $this->_copy_if_set($this->icon_highres_path, $destination_path, "icon@2x.png");
        $success &= $this->_copy_if_set($this->logo_path, $destination_path, "logo.png");
        $success &= $this->_copy_if_set($this->logo_highres_path, $destination_path, "logo@2x.png");
        $success &= $this->_copy_if_set($this->thumbnail_path, $destination_path, "thumbnail.png");
        $success &= $this->_copy_if_set($this->thumbnail_highres_path, $destination_path, "thumbnail@2x.png");
        $success &= $this->_copy_if_set($this->strip_path, $destination_path, "strip.png");
        $success &= $this->_copy_if_set($this->strip_highres_path, $destination_path, "strip@2x.png");

        return $success;
    }

    public function save_manifest_file($pass_path, $destination_path)
    {
        $manifest_dict = array();
        $files = scandir($pass_path);
        foreach ($files as $filename) {
            if ($filename[0] != '.') {
                $filepath = Util::path_join(array($pass_path, $filename));
                $contents = file_get_contents($filepath);
                $manifest_dict[$filename] = sha1($contents);
            }
        }

        $manifest_data = json_encode($manifest_dict);
        return file_put_contents($destination_path, $manifest_data);
    }

    public function save_signature_file($manifest_path, $destination_path)
    {
        assert(isset($this->certificate_path), "Path to certificate file not provided.");
        assert(isset($this->private_key_path), "Path to private key not provided.");
        assert(isset($this->wwdr_path), "Path to WWDR certificate not provided.");
        assert(isset($this->certificate_password), "Password for the certificate file was not provided.");

        $success = false;

        $pkcs12 = file_get_contents($this->certificate_path);
        $certs = array();
        $open_result = openssl_pkcs12_read($pkcs12, $certs, $this->certificate_password);
        if ($open_result) {
            $certdata = openssl_x509_read($certs["cert"]);
            $private_key = openssl_pkey_get_private($certs["pkey"], $this->certificate_password);

            if (!file_exists($this->wwdr_path)) {
                throw new Exception("Cannot open WWDR certificate.");
            }

            $signature_filepath = tempnam(sys_get_temp_dir(), "signature");
            $headers = array();
            $success = openssl_pkcs7_sign($manifest_path, $signature_filepath, $certdata, $private_key, $headers, PKCS7_BINARY | PKCS7_DETACHED, $this->wwdr_path);
            if (!$success) {
                error_log(openssl_error_string());
                throw new Exception("Could not create signature for manifest at path " . $manifest_path);
            }

            $signature = file_get_contents($signature_filepath);
            $der_signature = $this->_convert_pem_to_der($signature);
            $success = file_put_contents($destination_path, $der_signature);
            if (!$success) {
                throw new Exception("Could not save signature file at path " . $destination_path);
            }
        } else {
            error_log(openssl_error_string());
            throw new Exception("Could not open certificate at path " . $this->certificate_path);
        }

        return $success;
    }

    public function save_final_pass_package($pass_path, $destination_path)
    {
        // create the zip file
        $zip = new ZipArchive();
        $success = $zip->open($destination_path, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if (!$success) {
            error_log($zip->getStatusString());
            throw new Exception("Could not create zip archive at path " . $destination_path);
        }

        $files = scandir($pass_path);
        foreach ($files as $filename) {
            if ($filename[0] != '.') {
                $filepath = Util::path_join(array($pass_path, $filename));
                $zip->addFile($filepath, $filename);
            }
        }

        $success = $zip->close();
		if (!$success) {
			error_log($zip->getStatusString());
		}
        return $success;
    }

    public function save_pass($destination_path)
    {
        $tempdir = sys_get_temp_dir();
        $pass_dir = Util::path_join(array($tempdir, sprintf("pass_%d", rand())));
        $success = mkdir($pass_dir, 0777, true);
        if (!$success) {
            throw new Exception("Could not create temporary pass directory at " . $pass_dir);
        }

		$dest_dir = dirname($destination_path);
		error_log("DIRNAME: " . $dest_dir);
		if (!is_dir($dest_dir)) {
			mkdir($dest_dir, 0777, true);
		}

        error_log("Creating pass at temporary path " . $pass_dir);

        $json_path = Util::path_join(array($pass_dir, "pass.json"));
        $manifest_path = Util::path_join(array($pass_dir, "manifest.json"));
        $signature_path = Util::path_join(array($pass_dir, "signature"));
        $final_pass_path = Util::path_join(array($destination_path, "pass.pkpass"));

        $success = $this->save_pass_json($json_path);
        if (!$success) {
            throw new Exception("Failed to save json file to path " . $json_path);
        }
        $success = $this->copy_asset_files($pass_dir);
        if (!$success) {
            throw new Exception("Failed to copy asset files to path " . $pass_dir);
        }
        $success = $this->save_manifest_file($pass_dir, $manifest_path);
        if (!$success) {
            throw new Exception("Failed to save manifest file to " . $manifest_path);
        }
        $success = $this->save_signature_file($manifest_path, $signature_path);
        if (!$success) {
            throw new Exception("Failed to save signature file to " . $signature_path);
        }
        $success = $this->save_final_pass_package($pass_dir, $destination_path);
        if (!$success) {
            throw new Exception("Failed to package zip file.");
        }

        return $success;
    }

    /* Internal */

    protected function _set_if_value(&$dict, $key, $value)
    {
        if (isset($value)) {
            $dict[$key] = $value;
        }
    }

    protected function _set_if_color(&$dict, $key, $color)
    {
        if (isset($color)) {
            $color_str = sprintf("rgb(%d, %d, %d)", $color[0], $color[1], $color[2]);
            $dict[$key] = $color_str;
        }
    }

    protected function _insert_fields(&$dict, $fields_type_key, array $fields)
    {
        if (isset($fields) && count($fields) > 0) {
            $field_dicts = array();
            foreach ($fields as $field) {
                $field_dict = array();
                $field_dict["key"] = $field->key;
                $field_dict["value"] = $field->value;
                $field_dict["label"] = $field->label;
                $field_dicts[] = $field_dict;
            }

            if (!array_key_exists("storeCard", $dict)) {
                $dict["storeCard"] = array();
            }

            $dict["storeCard"][$fields_type_key] = $field_dicts;
        }
    }

    protected function _copy_if_set($source, $dest_dir, $dest_filename)
    {
        $success = true;
        if (isset($source)) {
            $dest = Util::path_join(array($dest_dir, $dest_filename));
            $success = copy($source, $dest);
        }
        return $success;
    }

    protected function _convert_pem_to_der($signature)
    {
        $begin = "filename=\"smime.p7s\"";
        $end = "------";
        $signature = substr($signature, strpos($signature, $begin) + strlen($begin));
        $signature = substr($signature, 0, strpos($signature, $end));
        $signature = trim($signature);
        $signature = base64_decode($signature);

        return $signature;
    }
}

class SMNetProfPass extends PassbookPass {
    public function __construct()
    {
        parent::__construct();

        $this->pass_type_identifier = "pass.com.magahern.smnetprofile";
        $this->format_version = 1;
        $this->team_identifier = "64S2YWUDC5";
        $this->organization_name = "Cyberdelia";
        $this->description = "Stepmania Net Profile Pass";

        $this->certificate_path = Util::path_join(array(PROJECT_ROOT, "support", "PassCertificate.p12"));
        $this->private_key_path = Util::path_join(array(PROJECT_ROOT, "support", "PassPrivateKey.pem"));
        $this->wwdr_path        = Util::path_join(array(PROJECT_ROOT, "support", "WWDR.pem"));
        $this->certificate_password = "OMHDGC1jh7UjcP";

        $this->back_fields[] = new PassbookField("splash_beats", "Just Got Splash Beats!");
        $this->back_fields[] = new PassbookField("address", "314 12th St\nSan Francisco, CA 94103", "Address");

        $this->icon_path = Util::get_asset_path("icon.png");
        $this->icon_highres_path = Util::get_asset_path("icon@2x.png");
        $this->logo_path = Util::get_asset_path("logo.png");
        $this->logo_highres_path = Util::get_asset_path("logo@2x.png");
        $this->strip_path = Util::get_asset_path("strip.png");
        $this->strip_highres_path = Util::get_asset_path("strip@2x.png");

        // $this->relevant_date = gmdate(TIMESTAMP_FORMAT, time());
        // XION SF: 37.7705119, -122.414685
        $this->locations[] = array("latitude" => 37.7705119, "longitude" => -122.414685);
    }
}

class UserPassbookPass extends SMNetProfPass {
    public $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;

        $this->logo_text = "DDR Stepmania Profile";
        $this->background_color = array(115, 0, 0); // red background
        $this->foreground_color = array(255, 255, 255);
        $this->label_color = array(255, 255, 255);

        $username_field = new PassbookField("display_name", $user->display_name, "Name");
        $credits_field = new PassbookField("credits", $user->num_credits, "Available Credits");
        $highscore_name_field = new PassbookField("highscore_name", $user->highscore_name, "High Score Name");

        $this->primary_fields[] = $username_field;
        $this->secondary_fields[] = $credits_field;
        $this->secondary_fields[] = $highscore_name_field;

        $uuid = $user->uuid;
        if (!isset($uuid)) {
            $uuid = uniqid();
            $user->uuid = $uuid;
            $user->save();
        }
        $this->serial_number = $uuid;
        $this->passbook_barcode = new PassbookBarcode($uuid);
    }
}

class BiscuitPassbookPass extends SMNetProfPass {
    public $biscuit;

    public function __construct(Biscuit $biscuit)
    {
        parent::__construct();
        $this->biscuit = $biscuit;

        $owner = $biscuit->get_owner();
        if (isset($owner)) {
            $this->logo_text = sprintf("%s's Stepmania Profile");
        } else {
            $this->logo_text = "Stepmania Profile (Unknown User)";
        }

        $this->background_color = array(0, 255, 0); // green background
        $this->foreground_color = array(255, 255, 255);
    }
}

?>
