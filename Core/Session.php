<?php

namespace JT\Core;

class Session
{
    protected static $instance;
    protected $session_id;

    protected $config = array(
        'id' => 'remixsid',                      # PHPSESSID or your version
        'domain' => null,                        # example.com or .example.com default $_SERVER['HTTP_HOST']
        'path' => '/',                           # Cookie path
        'secure' => null,                        # Cookie secure
        'http_only' => true,                     # Cookie http only
        'session_id_key_len' => 40,              # Cookie session id key len
        'memcache' => false,                     # false or tcp://127.0.0.1:11211?persistent=0&amp;weight=1&amp;timeout=1&amp;retry_interval=15
        'redis' => false,                        # false or tcp://127.0.0.1:6379?auth=123
    );

    protected function __clone()
    {
    }

    public static function start($config = null)
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

        $this->config = array_merge($this->config, $config);

        if ($this->config['domain'] == null)
            $this->config['domain'] = Server::host();

        if ($this->config['secure'] === null)
            $this->config['secure'] = Server::is_secure();

        if ($this->config['memcache']) {
            session_module_name('memcache');
            session_save_path($this->config['memcache']);
        } else if ($this->config['redis']) {
            session_module_name('redis');
            session_save_path($this->config['redis']);
        }

        if (isset($_COOKIE[$this->config['name']]) && preg_match('/^[a-zA-Z0-9]{' . $this->config['session_id_key_len'] . '}$/', $_COOKIE[$this->config['name']])) {
            $this->session_id = $_COOKIE[$this->config['name']];
        } else {
            $this->session_id = Hash::az09($this->config['session_id_key_len']);
            session_id($this->session_id);
        }

        session_name($this->config['name']);
        session_set_cookie_params(0, $this->config['path'], $this->config['domain'], $this->config['secure'], $this->config['http_only']);
        session_start();
    }

    public static function get($key = '')
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    public static function set($key, $value = null)
    {
        if(is_array($key)){
            foreach($key as $k => $v){
                $_SESSION[$k] = $v;
            }
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public static function del($key = '')
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return true;
    }

    public static function session_id()
    {
        return session_id();
    }

    public static function destroy()
    {
        session_destroy();
    }

}