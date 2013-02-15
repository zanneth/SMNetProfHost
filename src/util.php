<?php
/*
 * util.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/10/2013
 */

define("PROJECT_ROOT", dirname(dirname(__FILE__)));

class Util {
    static function path_join(array $path_components)
    {
        $filepath = "";
        foreach ($path_components as $idx => $component) {
            $filepath .= $component;
            if ($idx < count($path_components) - 1) {
                $filepath .= DIRECTORY_SEPARATOR;
            }
        }

        return $filepath;
    }

    static function get_asset_path($asset_filename)
    {
        return Util::path_join(array(PROJECT_ROOT, "assets", $asset_filename));
    }

    static function config_get_key($key)
    {
        static $__config_dict = NULL;
        if (is_null($__config_dict)) {
            $config_filepath = Util::path_join(array(PROJECT_ROOT, "config", "config.json"));
            $config_contents = file_get_contents($config_filepath);
            $__config_dict = json_decode($config_contents);
        }

        return $__config_dict->{$key};
    }

    static function get_description($object)
    {
        ob_start();
        var_dump($object);
        $buf = ob_get_contents();
        ob_end_clean();
        
        return $buf;
    }

    static function log_description($object)
    {
        $descr = self::get_description($object);
        error_log($descr);
    }

    static function verify_required_keys(array $dict, array $required_keys, &$out_missing_keys = array())
    {
        $missing_keys = array();
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $dict)) {
                $missing_keys[] = $key;
            }
        }

        $out_missing_keys = $missing_keys;
        return (count($missing_keys) == 0);
    }

    static function redirect($url)
    {
        $header_str = sprintf("Location: %s", $url);
        header($header_str);
    }
}

?>
