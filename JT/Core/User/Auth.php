<?php


namespace JT\Core\User;

use JT\Core\Hash;
use JT\Core\Server;
use JT\Core\Config;
use JT\Core\Session;
use JT\Core\MySQLi\MySQLiStatic AS db;
use JT\Core\Cookie\CookieStatic AS cookie;


class Auth
{
    protected static $instance;
    protected static $id;

    private static $config = [
        'salt_len' => 8,
        'session_time_life' => 31,
        'session_secret' => '',
        'session_name' => 'remixssid',
        'session_exp_name' => 'remixexp',
        'session_hash_len' => 40,
        'hash_algos' => 'gost'
    ];

    private static $is_login = false;
    private static $quick_expire = false;

    static $fields = "`id`, `mail`, `phone`, `status`, `password`, `deleted`";
    static $authStatus = 0;
    static $data = [];

    const AUTH_STATUS_OK = 1;
    const AUTH_STATUS_NOT_FOUND = -1;
    const AUTH_STATUS_INCORRECT_PASSWORD = -2;
    const AUTH_STATUS_DELETED = -3;

    protected function __clone()
    {
    }

    public static function init($config = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    function __construct($config)
    {
        if (is_string($config)) {
            $config = Config::get($config, []);
        }

        if (!is_array($config)) {
            $config = [];
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        self::$config = array_merge(self::$config, $config);
        self::$config['session_time_life'] = self::$config['session_time_life'] * 24 * 60 * 60 + time();

        if (isset($_SESSION['user_id'])) {
            self::$id = $_SESSION['user_id'];
            self::$is_login = true;
        }
    }

    public static function is_login()
    {
        return self::$is_login;
    }

    public static function id()
    {
        return self::$id;
    }

    public static function byMailPassword($mail, $password, $quick_expire = false)
    {
        if (!self::$data = self::infoByMail($mail)) {
            self::$authStatus = self::AUTH_STATUS_NOT_FOUND;
            return false;
        }

        if (intval(self::$data['deleted']) === 1) {
            self::$authStatus = self::AUTH_STATUS_DELETED;
            return false;
        }

        if (!Hash::check(self::$config['hash_algos'], $password, self::$config['salt_len'], self::$data['password'])) {
            self::$authStatus = self::AUTH_STATUS_INCORRECT_PASSWORD;
            return false;
        }

        self::$id = self::$data['id'];
        self::$authStatus = self::AUTH_STATUS_OK;
        self::$quick_expire = $quick_expire;
        return true;
    }

    public static function byCookie()
    {
        if (!$hash = cookie::get(self::$config['session_name'])) {
            return false;
        }
        //$hash = hash(self::$config['hash_algos'], $hash . self::$config['session_secret']);
        if (!$user_id = db::get_value_query("SELECT `user_id` FROM `user_session` WHERE `session_hash`=$ AND `expires` > NOW() LIMIT 1", $hash)) {
            return false;
        }

        if (!self::$data = self::infoByID($user_id)) {
            self::$authStatus = self::AUTH_STATUS_NOT_FOUND;
            return false;
        }

        if (intval(self::$data['deleted']) === 1) {
            self::$authStatus = self::AUTH_STATUS_DELETED;
            return false;
        }

        self::$id = self::$data['id'];
        self::$authStatus = self::AUTH_STATUS_OK;
        self::$quick_expire = !!(cookie::get(self::$config['session_exp_name']));
        return true;
    }

    public static function byID($user_id)
    {
        if (!self::$data = self::infoByID($user_id)) {
            self::$authStatus = self::AUTH_STATUS_NOT_FOUND;
            return false;
        }

        if (intval(self::$data['deleted']) === 1) {
            self::$authStatus = self::AUTH_STATUS_DELETED;
            return false;
        }

        self::$id = self::$data['id'];
        self::$authStatus = self::AUTH_STATUS_OK;
        self::$quick_expire = !!(cookie::get(self::$config['session_exp_name']));
        return true;
    }

    public static function authorize()
    {
        if (self::$authStatus !== self::AUTH_STATUS_OK || !self::$id) {
            return false;
        }

        if (!$hash = cookie::get(self::$config['session_name'])) {
            $hash = Hash::az09(self::$config['session_hash_len']);
        }

        if (strlen($hash) != self::$config['session_hash_len']) {
            $hash = Hash::az09(self::$config['session_hash_len']);
        }

        if (!self::dbSessionAdd($hash)) {
            return false;
        }

        if (!self::$quick_expire) {
            cookie::set(self::$config['session_name'], $hash, self::$config['session_time_life']);
            cookie::del(self::$config['session_exp_name']);
        } else {
            cookie::set(self::$config['session_name'], $hash, null);
            cookie::set(self::$config['session_exp_name'], '1', null);
        }

        $_SESSION['user_id'] = self::$data['id'];
        return true;
    }

    private static function dbSessionAdd($hash)
    {
        $user_agent = Server::user_agent();
        $ip = Server::ip();
        //$hash = hash(self::$config['hash_algos'], $hash . self::$config['session_secret']);

        return db::query("INSERT INTO `user_session` (`user_id`, `session_hash`, `expires`, `ip_address`,`user_agent`) VALUES ($,$,FROM_UNIXTIME($),INET_ATON($),$) ON DUPLICATE KEY UPDATE `expires`=FROM_UNIXTIME($), `ip_address`=INET_ATON($), `user_agent`=$",
            [
                self::$data['id'],
                $hash,
                self::$config['session_time_life'],
                $ip,
                $user_agent,
                self::$config['session_time_life'],
                $ip,
                $user_agent
            ]
        );
    }

    private static function dbSessionDel($hash)
    {
        // todo check with logout
        if(!isset(self::$data['id']))
            return false;
        //$hash = hash(self::$config['hash_algos'], $hash . self::$config['session_secret']);
        return db::query("UPDATE `user_session` SET `expires`=NOW() WHERE `user_id`=$ AND `session_hash`=$", array(self::$data['id'], $hash));
    }

    public static function infoByMail($mail)
    {
        if (!$mail)
            return false;

        return db::get_one_line_assoc("SELECT " . self::$fields . " FROM `user` WHERE `mail`=$ AND `deleted`=0 LIMIT 1", $mail);
    }

    public static function infoByID($user_id)
    {
        if (!$user_id)
            return false;

        return db::get_one_line_assoc("SELECT " . self::$fields . " FROM `user` WHERE `id`=$ AND `deleted`=0 LIMIT 1", $user_id);
    }

    public static function logout($location)
    {
        if ($hash = cookie::get(self::$config['session_name'])) {
            self::dbSessionDel($hash);
        }
        cookie::del(self::$config['session_name']);
        cookie::del(self::$config['session_exp_name']);
        session_destroy();
        if ($location) {
            header('Location: ' . $location);
            die();
        }
    }

}