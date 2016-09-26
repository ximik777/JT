<?php

namespace JT\Core;

class Autoload
{
    public static $vendor;

    public static function Register($prepend = false)
    {
        self::$vendor = substr(__DIR__, 0, strrpos(__DIR__, strtr('/'.__NAMESPACE__, '\\', '/')));

        if (PHP_VERSION_ID < 50300) {
            spl_autoload_register(array(__CLASS__, 'Autoload'));
        } else {
            spl_autoload_register(array(__CLASS__, 'Autoload'), true, $prepend);
        }
    }

    public static function Autoload($class)
    {

        if (0 !== strpos($class, 'Twig')) {
            $file = ((strncmp($class, 'bundle', 6) === 0) ? DR : self::$vendor) . '/' . strtr($class, '\\', '/') . '.php';
        } else {
            $file = self::$vendor.'/Twig/lib/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php';
        }

        if (is_file($file))
            require_once($file);
    }
}


