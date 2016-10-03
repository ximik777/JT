<?php

namespace JT\Core;

class Session
{
    protected static $instance;
    protected $sid;
    protected $ssid;

    protected $config = array(
        'sid' => 'remixsid',                     # PHPSESSID or your version
        'sid_len' => 40,                         # Cookie session id key len

        'ssid' => 'remixssid',
        'exp' => 'remixexp',
        'ssid_len' => 60,                        # Cookie session id key len

        'domain' => null,                        # example.com or .example.com default $_SERVER['HTTP_HOST']
        'path' => '/',                           # Cookie path
        'secure' => null,                        # Cookie secure
        'http_only' => true,                     # Cookie http only

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

        if (isset($_COOKIE[$this->config['sid']]) && preg_match('/^[a-zA-Z0-9]{' . $this->config['sid_len'] . '}$/', $_COOKIE[$this->config['sid']])) {
            $this->sid = $_COOKIE[$this->config['sid']];
        } else {
            $this->sid = Hash::az09($this->config['sid_len']);
            session_id($this->sid);
        }

        session_name($this->config['sid']);
        session_set_cookie_params(0, $this->config['path'], $this->config['domain'], $this->config['secure'], $this->config['http_only']);
        session_start();
    }

    public static function lifeTime()
    {
        static $life_time;
        if ($life_time) {
            return $life_time;
        }
        $life_time = (int)ini_get('session.gc_maxlifetime');
        return $life_time;
    }

    public static function get($key = '')
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    public static function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
        } else {
            $_SESSION[$key] = $value;
        }

        return true;
    }

    public static function del($key = '')
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return true;
    }

    public static function id()
    {
        return session_id();
    }

    public static function sid_is_exp()
    {
        return !!(isset($_COOKIE[self::$instance->config['exp']]));
    }

    public static function sid_rm_exp()
    {
        return !!(isset($_COOKIE[self::$instance->config['exp']]));
    }

    public static function sid_get()
    {
        if (isset($_COOKIE[self::$instance->config['ssid']]) && preg_match('/^[a-zA-Z0-9]{' . self::$instance->config['ssid_len'] . '}$/', $_COOKIE[self::$instance->config['ssid']])) {
            return $_COOKIE[self::$instance->config['ssid']];
        }
        return false;
    }

    public static function sid_set($time_life)
    {
        if (isset($_COOKIE[self::$instance->config['ssid']]) && preg_match('/^[a-zA-Z0-9]{' . self::$instance->config['ssid_len'] . '}$/', $_COOKIE[self::$instance->config['ssid']])) {
            self::$instance->ssid = $_COOKIE[self::$instance->config['ssid']];
        } else {
            self::$instance->ssid = Hash::az09(self::$instance->config['ssid_len']);
        }

        return self::sid_expire($time_life);
    }


    public static function sid_expire($time_life)
    {
        if ($time_life <= 0) {
            setcookie(self::$instance->config['exp'], '1', time() + $time_life, self::$instance->config['path'], self::$instance->config['domain'], self::$instance->config['secure'], self::$instance->config['http_only']);
        }
        setcookie(self::$instance->config['ssid'], self::$instance->ssid, $time_life ? time() + $time_life : 0, self::$instance->config['path'], self::$instance->config['domain'], self::$instance->config['secure'], self::$instance->config['http_only']);
        return self::$instance->ssid;
    }

    public static function destroy()
    {
        self::sid_expire(-3600);
        session_destroy();
    }
}