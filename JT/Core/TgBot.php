<?php

namespace JT\Core;

class TgBot
{
    const API_URL = 'https://api.telegram.org';
    static $key = null;

    static function init($key)
    {
        self::$key = $key;
    }

    private static function request($method, $data = array())
    {
        if(!self::$key){
            return false;
        }

        $url = "/bot".self::$key."/{$method}" . (!empty($data) ? '?' . http_build_query($data) : '');

        $json = file_get_contents(self::API_URL . $url);

        if ($json && $json = json_decode($json)) {
            return $json;
        }

        return false;
    }

    public static function sendMessage($chat_id, $text, $key = null)
    {
        if (!$chat_id || !$text) return false;

        if($key){
            self::$key = $key;
        }

        return self::request('sendMessage', ['chat_id' => $chat_id, 'text' => $text]);
    }

    public static function sendSticker($chat_id, $sticker, $key = null)
    {
        if (!$chat_id || !$sticker) return false;

        if($key){
            self::$key = $key;
        }

        return self::request('sendSticker', ['chat_id' => $chat_id, 'sticker' => $sticker]);
    }
}