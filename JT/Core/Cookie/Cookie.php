<?php

namespace JT\Core\Cookie;

use JT\Core\Config;
use JT\Core\Server;

class Cookie
{

    private $config = array(
        'path' => '/',                           # Cookie path
        'domain' => null,                        # example.com or .example.com default $_SERVER['HTTP_HOST']
        'secure' => null,                        # Cookie secure
        'http_only' => true,                     # Cookie http only
    );

    private $path = '/';
    private $domain = null;
    private $secure = null;
    private $http_only = null;

    public function __construct($config = null)
    {
        if (is_string($config)) {
            $config = Config::get($config, []);
        }

        if (!is_array($config)) {
            $config = [];
        }

        $this->config = array_merge($this->config, $config);

        $this->path = $this->config['path'];
        $this->domain = $this->config['domain'];
        $this->secure = $this->config['secure'];
        $this->http_only = $this->config['http_only'];

        if (!$this->domain) {
            $this->domain = Server::host();
        }

        if ($this->secure === null)
            $this->secure = Server::is_secure();
    }

    public function set($name, $value, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if ($path)
            $this->path = $path;
        if ($domain)
            $this->domain = $domain;
        if ($secure)
            $this->secure = $secure;
        if ($httponly)
            $this->httponly = $httponly;

        return setcookie($name, $value, $expires, $this->path, $this->domain, $this->secure, $this->http_only);
    }

    public function get($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return null;
    }

}