<?php

namespace JT\Core\Cookie;


class CookieStatic
{
    protected static $instance;

    protected function __clone()
    {
    }

    public static function init($config = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new Cookie($config);
        }
        return self::$instance;
    }

    public static function set($name, $value, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        self::$instance->set($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    public static function del($name)
    {
        self::$instance->set($name, null, -time());
    }

    public static function get($name)
    {
        return self::$instance->get($name);
    }
}
