<?php

namespace JT\Core;

class Cookie
{
    protected static $instance;

    protected $config = array(
        'path' => '/',                           # Cookie path
        'domain' => null,                        # example.com or .example.com default $_SERVER['HTTP_HOST']
        'secure' => null,                        # Cookie secure
        'http_only' => true,                     # Cookie http only
    );

    public static function init($config = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function __construct($config = null)
    {
        if (is_string($config)) {
            $config = Config::get($config, []);
        }

        if (!is_array($config)) {
            $config = [];
        }

        $this->config = array_merge($this->config, $config);

        if (!$this->config['domain']) {
            $this->config['domain'] = Server::host();
        }

        if ($this->config['secure'] === null)
            $this->config['secure'] = Server::is_secure();
    }

    public static function set($name, $value, $expires = null, $path = null, $domain = null, $secure = null, $http_only = null)
    {
        if (!$path)
            $path = self::$instance->config['path'];
        if (!$domain)
            $domain = self::$instance->config['domain'];
        if ($secure === null)
            $secure = self::$instance->config['secure'];
        if ($http_only)
            $http_only = self::$instance->config['http_only'];

        return setcookie($name, $value, $expires, $path, $domain, $secure, $http_only);
    }

    public static function del($name)
    {
        self::set($name, null, -time());
    }

    public static function get($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return null;
    }

}