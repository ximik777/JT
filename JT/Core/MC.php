<?php

namespace JT\Core;

class MC
{
    protected static $instance;

    protected $handle;

    protected $config = array(
        'host' => 'localhost',
        'port' => 11211,
        'compress' => false,
        'prefix' => '',
        'time_life' => 0,
        'persistent' => false
    );

    protected function __clone()
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

        $this->config = array_merge($this->config, $config);
        $this->handle = new \Memcache;
        $connect = $this->config['persistent'] ? $this->handle->pconnect($this->config['host'], $this->config['port']) : $this->handle->connect($this->config['host'], $this->config['port']);
        if (!$connect) {
            throw new \Exception("Memcache is not connected", E_WARNING);
        }
    }

    public static function getHandle()
    {
        return self::$instance->handle;
    }

    protected static function val($val)
    {
        $_this = self::$instance;
        $_this->config['compress'] = !$_this->config['compress'] ? false : is_bool($val) || is_int($val) || is_float($val) ? false : MEMCACHE_COMPRESSED;
        return $val;
    }

    private static function key($key)
    {
        $_this = self::$instance;
        if (is_array($key)) {
            $data = array();
            foreach ($key as $k => $v) {
                $data[] = $_this->config['prefix'] . $v;
            }
            return $data;
        }
        return $_this->config['prefix'] . $key;
    }

    public static function get($key)
    {
        return self::$instance->handle->get(self::key($key));
    }

    public static function set($key, $val, $time = false)
    {
        $_this = self::$instance;
        return $_this->handle->set($_this->config['prefix'] . $key, self::val($val), $_this->config['compress'], $time ? $time : $_this->config['time_life']);
    }

    public static function add($key, $val, $time = false)
    {
        $_this = self::$instance;
        return $_this->handle->add($_this->config['prefix'] . $key, self::val($val), $_this->config['compress'], $time ? $time : $_this->config['time_life']);
    }

    public static function replace($key, $val, $time = false)
    {
        $_this = self::$instance;
        return $_this->handle->replace($_this->config['prefix'] . $key, self::val($val), $_this->config['compress'], $time ? $time : $_this->config['time_life']);
    }

    public static function del($key)
    {
        return self::$instance->handle->delete(self::key($key));
    }

    public static function increment($key, $val = 1)
    {
        $_this = self::$instance;
        return $_this->handle->increment($_this->config['prefix'] . $key, abs(intval($val)));
    }

    public static function decrement($key, $val = 1)
    {
        $_this = self::$instance;
        return $_this->handle->decrement($_this->config['prefix'] . $key, abs(intval($val)));
    }

    public function __destruct()
    {
        if (self::$instance->handle)
            self::$instance->handle->close();
    }

}