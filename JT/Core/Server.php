<?php


namespace JT\Core;


class Server
{

    public static function host()
    {
        static $host;
        if (!is_null($host)) {
            return $host;
        }
        if ($host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : false)) {
            $host = trim(end(explode(',', $host)));
        } else {
            if (!$host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false)) {
                if (!$host = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : false)) {
                    $host = !empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
                }
            }
        }

        $host = trim(strtok($host, ':'));
        return $host;
    }

    public static function is_secure()
    {
        static $secure;
        if (!is_null($secure)) {
            return $secure;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) && $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] == 'https')
            $secure = true;
        elseif (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']))
            $secure = true;
        elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443)
            $secure = true;
        else
            $secure = false;
        return $secure;
    }

    static function ip($ip_param_name = null)
    {
        static $ip;
        if (!is_null($ip)) {
            return $ip;
        }
        if (is_string($ip_param_name) && isset($_SERVER[$ip_param_name])) {
            $ip = $_SERVER[$ip_param_name];
        } else {
            if (isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (strpos($ip, ',') !== false) {
                    $ip = array_pop(explode(',', $ip));
                }
            } elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip = "";
            }
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = false;
        }
        return $ip;
    }

    static function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    static function user_agent($agent = false)
    {
        static $user_agent;
        if (!is_null($user_agent)) {
            return $user_agent;
        }
        if (!$agent) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        $browser = null;
        $version = null;
        if (preg_match("/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/", $agent, $browser_info)) {
            list(, $browser, $version) = $browser_info;
        }
        $user_agent = $browser . ' ' . $version;

        if (preg_match("/Opera ([0-9.]+)/i", $agent, $opera)) {
            $user_agent = 'Opera ' . $opera[1];
        } elseif ($browser == 'MSIE') {
            preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie);
            if ($ie) {
                $user_agent = $ie[1] . ' based on IE ' . $version;
            } else {
                $user_agent = 'IE ' . $version;
            }
        } elseif ($browser == 'Firefox' && preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff)) {
            if ($ff) {
                $user_agent = $ff[1] . ' ' . $ff[2];
            }
        } elseif ($browser == 'Opera' && $version == '9.80') {
            $user_agent = 'Opera ' . substr($agent, -5);
        } elseif ($browser == 'Version') {
            $user_agent = 'Safari ' . $version;
        } elseif (!$browser && strpos($agent, 'Gecko')) {
            $user_agent = 'Browser based on Gecko';
        }
        return $user_agent;
    }

    //                     first default lang
    // $aLanguages = array('ru'=>'ru', 'en'=>'en');
    public static function lang($aLanguages = array(), $sWhere = false)
    {

        // Устанавливаем текущий язык как язык по умолчанию
        $sLanguage = array_keys($aLanguages)[0];

        if (!$sWhere) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $sWhere = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            } else {
                return $sLanguage;
            }
        }

        // Изначально используется лучшее качество
        $fBetterQuality = 0;

        // Поиск всех подходящих парметров
        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i", $sWhere, $aMatches, PREG_SET_ORDER);
        foreach ($aMatches as $aMatch) {

            // Устанавливаем префикс языка
            $sPrefix = strtolower($aMatch[1]);

            // Подготоваливаем временный язык
            $sTempLang = (empty($aMatch[3])) ? $sPrefix : $sPrefix . '-' . strtolower($aMatch[3]);

            // Получаем значения качества (если оно есть)
            $fQuality = (empty($aMatch[5])) ? 1.0 : floatval($aMatch[5]);

            if ($sTempLang) {

                // Определяем наилучшее качество
                if ($fQuality > $fBetterQuality && in_array($sTempLang, array_keys($aLanguages))) {

                    // Устанавливаем текущий язык как временный и обновляем значение качества
                    $sLanguage = $sTempLang;
                    $fBetterQuality = $fQuality;
                } elseif (($fQuality * 0.9) > $fBetterQuality && in_array($sPrefix, array_keys($aLanguages))) {

                    // Устанавливаем текущий язык как значение префикса и обновляем значение качества
                    $sLanguage = $sPrefix;
                    $fBetterQuality = $fQuality * 0.9;
                }
            }
        }
        return $sLanguage;
    }

}