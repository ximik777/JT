<?php


namespace JT\Core;

if(!function_exists('random_int')){
    function random_int($min, $max){
        return mt_rand($min, $max);
    }
}

class Hash
{

    public static function az($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ';
        $chars_len = 51;
        $code = "";
        while ($length) {
            $code .= $chars[mt_rand(0, $chars_len)];
            $length--;
        }
        return $code;
    }

    public static function _09($length = 8)
    {
        $chars = '0123456789';
        $chars_len = 9;
        $code = "";
        while ($length) {
            $code .= $chars[mt_rand(0, $chars_len)];
            $length--;
        }
        return $code;
    }

    public static function az09($length = 8)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ';
        $chars_len = 61;
        $code = "";
        while ($length) {
            $code .= $chars[random_int(0, $chars_len)];
            $length--;
        }
        return $code;
    }

    public static function hex($length = 8)
    {
        $chars = '0123456789abcdef';
        $chars_len = 15;
        $code = "";
        while ($length) {
            $code .= $chars[mt_rand(0, $chars_len)];
            $length--;
        }
        return $code;
    }

    public static function create($hash_algorithm, $password = '', $salt_len = 0)
    {
        if (!$salt_len)
            return hash($hash_algorithm, $password);
        $salt = self::hex($salt_len);
        return $salt . (substr(hash($hash_algorithm, $salt . $password), $salt_len));
    }

    public static function check($hash_algorithm, $password = '', $salt_len = 0, $hash = '')
    {
        if (!$salt_len)
            return hash($hash_algorithm, $password) == $hash;
        return substr($hash, $salt_len) == substr(hash($hash_algorithm, substr($hash, 0, $salt_len) . $password), $salt_len);
    }

}