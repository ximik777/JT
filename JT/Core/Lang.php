<?php

namespace JT\Core;


class Lang
{
    private static $lang = array();
    private static $loaded = array();
    private static $instance;

    private static $config = array(
        'public_path' => '/static/lang/',
        'server_path' => './static/lang/',
    );

    private function __clone()
    {
    }

    public static function init($config = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    private function __construct($config = null)
    {
        if (is_string($config)) {
            $config = Config::get($config, []);
        }

        if (!is_array($config)) {
            $config = [];
        }

        self::$config = array_merge(self::$config, $config);
    }


    public static function generate($bundle_name, $lang, array $variable)
    {
        $path = realpath(self::$config['server_path']) . "/{$bundle_name}.{$lang}";
        $add = array("{$bundle_name}-{$lang}-created" => time()) + $variable;

        if (!file_put_contents($path . '.php', self::buildPHP($add)) ||
            !file_put_contents($path . '.js', self::buildJS($add))
        ) {
            return false;
        }

        return true;
    }

    public static function buildPHP(array $variable)
    {
        $text = "<?php\n\nreturn array(\n";
        $text_arr = array();
        foreach ($variable AS $k => $v) {
            $text_arr[] = "  '" . (addslashes($k)) . "' => '" . (addslashes(htmlspecialchars_decode($v, ENT_QUOTES))) . "'";
        }
        $text .= implode(",\n", $text_arr);
        $text .= "\n);";
        return $text;
    }

    public static function buildJS(array $variable)
    {
        $text = "if(!lang) lang = {};\n\n";
        foreach ($variable AS $k => $v) {
            $text .= "lang['" . (addslashes($k)) . "'] = '" . (addslashes(htmlspecialchars_decode($v, ENT_QUOTES))) . "';\n";
        }
        return $text;
    }

    public static function load($bundle_name, $lang = 'ru')
    {
        $path = realpath(self::$config['server_path']) . "/{$bundle_name}.{$lang}.php";
        $created = $bundle_name . '-' . $lang . '-created';

        if (isset(self::$loaded[$bundle_name])) {
            return true;
        }

        if (!file_exists($path))
            return false;

        $lang_data = include($path);

        if (!is_array($lang_data)) {
            return false;
        }

        self::$loaded[$bundle_name] = array($lang, isset($lang_data[$created]) ? $lang_data[$created] : 0);

        self::$lang = self::$lang + $lang_data;

        return true;
    }

    public static function get($key = false, $return_false_is_not_found = false)
    {
        if (!$key || !isset(self::$lang[$key]))
            return $return_false_is_not_found ? false : '≠' . (str_replace(array('_', '-'), ' ', $key));

        return self::$lang[$key];
    }


    public static function loadJS()
    {
        $text = '';
        foreach (self::$loaded as $k => $v) {
            $text .= '<script type="text/javascript" src="' . (self::$config['public_path'] . $k . '.' . $v[0] . '.js') . '?' . $v[1] . '"></script>' . "\r\n";
        }
        return $text;
    }

    public static function loadedJS()
    {
        $list = [];
        foreach (self::$loaded as $k => $v) {
            $list[] = self::$config['public_path'] . $k . '.' . $v[0] . '.js' . '?' . $v[1];
        }
        return $list;
    }

    public static function getAll()
    {
        return self::$lang;
    }

}