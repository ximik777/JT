<?php

namespace JT\Core;

class Utils
{

    public static function register_error_handler()
    {
        set_error_handler([__CLASS__, 'error_handler'], E_ALL);
        set_exception_handler([__CLASS__, 'exception_handler']);
        register_shutdown_function([__CLASS__, 'shutdown_handler']);
    }

    public static function error_handler($code, $message, $file, $line)
    {
//        if (!config::$show_error)
//            return false;

        switch ($code) {
            case E_ERROR:
                $errType = 'Error';
                break;
            case E_WARNING:
                $errType = 'Warning';
                break;
            case E_PARSE:
                $errType = 'Parse Error';
                break;
            case E_NOTICE:
                $errType = 'Notice';
                break;
            case E_CORE_ERROR:
                $errType = 'Core Error';
                break;
            case E_CORE_WARNING:
                $errType = 'Core Warning';
                break;
            case E_COMPILE_ERROR:
                $errType = 'Compile Error';
                break;
            case E_COMPILE_WARNING:
                $errType = 'Compile Warning';
                break;
            case E_USER_ERROR:
                $errType = 'User Error';
                break;
            case E_USER_WARNING:
                $errType = 'User Warning';
                break;
            case E_USER_NOTICE:
                $errType = 'User Notice';
                break;
            case E_STRICT:
                $errType = 'Strict Notice';
                break;
            case E_RECOVERABLE_ERROR:
                $errType = 'Recoverable Error';
                break;
            default:
                $errType = "Unknown error ($code)";
                break;
        }
        if ($code == E_NOTICE || $code == E_USER_NOTICE)
            print '<div style="border:2px solid #CCC; padding:3px; font-size:10px; color:#666; background:#e2e2e2">';
        else
            print '<div style="border:2px solid #F00; padding:3px; font-size:10px; color:#F00; background:#FFE5E5">';
        print 'Системная ошибка <b>[' . $errType . ']</b><br>';
        print $message . '<br>[' . $line . '] - ' . $file;
        print '</div>';
        return true;
    }

    public static function exception_handler(\Error $e)
    {
        self::error_handler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }

    public static function shutdown_handler()
    {
        $error = error_get_last();
        if ($error && ($error['type'] == E_ERROR || $error['type'] == E_PARSE || $error['type'] == E_COMPILE_ERROR)) {
            if (strpos($error['message'], 'Allowed memory size') === 0) {
                ini_set('memory_limit', (intval(ini_get('memory_limit')) + 64) . "M");
            }
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    static function is_secure()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) && $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] == 'https')
            return true;
        if ((isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443)
            return true;
        return false;
    }

    static function ip($ip_param_name = null)
    {
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
            return false;
        }
        return $ip;
    }

    static function user_agent($agent = false)
    {
        if (!$agent) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        $browser = null;
        $version = null;
        if (preg_match("/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/", $agent, $browser_info)) {
            list(, $browser, $version) = $browser_info;
        }
        if (preg_match("/Opera ([0-9.]+)/i", $agent, $opera))
            return 'Opera ' . $opera[1];
        if ($browser == 'MSIE') {
            preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie);
            if ($ie) return $ie[1] . ' based on IE ' . $version;
            return 'IE ' . $version;
        }
        if ($browser == 'Firefox') {
            preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff);
            if ($ff) return $ff[1] . ' ' . $ff[2];
        }
        if ($browser == 'Opera' && $version == '9.80') return 'Opera ' . substr($agent, -5);
        if ($browser == 'Version') return 'Safari ' . $version;
        if (!$browser && strpos($agent, 'Gecko')) return 'Browser based on Gecko';
        return $browser . ' ' . $version;
    }

    static function create_access_token($length)
    {
        $chars = ''
            . 'CUvNw8_TYRQOtpHKo6iPIZFu20XBry3mEDqxcfnldekj7M-4h9VAazL5b1SJgsGW'
            . 'qvNAhdoiYu5nJSCLXs-Fpbz4ljwxD70UP6G9B3t1krfe2VRWg_cTHMQOm8ZaEKIy'
            . 'Kd0wh-qaspBOt3NRcxHyk8M9gCGJPrzfn2ViW6eEbY7om_1lFSuLXUADQZ4vTjI5'
            . 'UJQK0bOgwkGPzm_dcHvqsxNWFlTLe42orB1fipaZt9YShIEAnX-R3Mu65DVyC78j';
        $code = "";
        while ($length) {
            $code .= $chars[mt_rand(0, 255)];
            $length--;
        }
        return $code;
    }


    static function get_random_code($length = 8, $hex = false)
    {
        $chars = $hex ? 'abcdef' : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ';
        $chars .= '0123456789';
        $chars_len = strlen($chars) - 1;
        $code = "";
        while ($length) {
            $code .= $chars[mt_rand(0, $chars_len)];
            $length--;
        }
        return $code;
    }

    static function transliteration($text = '', $back = false, $space = ' ')
    {
        $chars = array(
            array(
                ' ', 'ий', 'ё', 'ж', 'х', 'ц', 'ч', 'щ', 'щ', 'ш', 'э', 'ю', 'я', 'Ё', 'Ж', 'Х', 'Ц', 'Ч', 'Щ', 'Щ', 'Ш', 'Э', 'Ю', 'Я', 'ь',
                'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ы', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ы', 'Ё'
            ),
            array(
                ' ', 'y', 'yo', 'zh', 'kh', 'ts', 'ch', 'sch', 'shch', 'sh', 'eh', 'yu', 'ya', 'YO', 'ZH', 'KH', 'TS', 'CH', 'SCH', 'SHCH', 'SH', 'EH', 'YU', 'YA', '',
                'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Y', 'E'
            )
        );
        $chars[(int)(!($back))][0] = $space;
        return str_replace($chars[(int)(!!($back))], $chars[(int)(!($back))], $text);
    }

    static function validate_phone($phone = '')
    {
        $phone = substr(preg_replace('/\D/', '', $phone), -10);
        if (empty($phone) || strlen($phone) !== 10 || $phone{0} !== '9') return false;
        return $phone;
    }

    static function validate_login($login = '')
    {
        return preg_match('/^[.0-9a-zA-Z_]{2,32}$/u', $login);
    }

    static public function getMac($prefix = '')
    {
        return strtoupper(substr($prefix . md5(time() . mt_rand()), 0, 12));
    }

    static function isMac($mac)
    {
        return preg_match('/^[a-fA-F0-9]{12}$/', $mac);
    }

    


    public static function unicode2cyrillic($string)
    {

        $chars = array(
            '\u0430' => 'а', '\u0410' => 'А',
            '\u0431' => 'б', '\u0411' => 'Б',
            '\u0432' => 'в', '\u0412' => 'В',
            '\u0433' => 'г', '\u0413' => 'Г',
            '\u0434' => 'д', '\u0414' => 'Д',
            '\u0435' => 'е', '\u0415' => 'Е',
            '\u0451' => 'ё', '\u0401' => 'Ё',
            '\u0436' => 'ж', '\u0416' => 'Ж',
            '\u0437' => 'з', '\u0417' => 'З',
            '\u0438' => 'и', '\u0418' => 'И',
            '\u0439' => 'й', '\u0419' => 'Й',
            '\u043a' => 'к', '\u041a' => 'К',
            '\u043b' => 'л', '\u041b' => 'Л',
            '\u043c' => 'м', '\u041c' => 'М',
            '\u043d' => 'н', '\u041d' => 'Н',
            '\u043e' => 'о', '\u041e' => 'О',
            '\u043f' => 'п', '\u041f' => 'П',
            '\u0440' => 'р', '\u0420' => 'Р',
            '\u0441' => 'с', '\u0421' => 'С',
            '\u0442' => 'т', '\u0422' => 'Т',
            '\u0443' => 'у', '\u0423' => 'У',
            '\u0444' => 'ф', '\u0424' => 'Ф',
            '\u0445' => 'х', '\u0425' => 'Х',
            '\u0446' => 'ц', '\u0426' => 'Ц',
            '\u0447' => 'ч', '\u0427' => 'Ч',
            '\u0448' => 'ш', '\u0428' => 'Ш',
            '\u0449' => 'щ', '\u0429' => 'Щ',
            '\u044a' => 'ъ', '\u042a' => 'Ъ',
            '\u044b' => 'ы', '\u042b' => 'Ы',
            '\u044c' => 'ь', '\u042c' => 'Ь',
            '\u044d' => 'э', '\u042d' => 'Э',
            '\u044e' => 'ю', '\u042e' => 'Ю',
            '\u044f' => 'я', '\u042f' => 'Я'
        );

        return str_replace(array_keys($chars), array_values($chars), $string);
    }


    static function get_hard_code($length = 8)
    {
        $char_arr = array(
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPRQSTUVWXYZ',
            '#$^&*_-+%@'
        );

        $code = "";
        $i = 0;
        while ($length) {
            $code .= $char_arr[$i][mt_rand(0, strlen($char_arr[$i]) - 1)];
            $i++;
            if ($i > (count($char_arr) - 1)) $i = 0;
            $length--;
        }
        return str_shuffle($code);
    }

    static function num2str($num, $dogovor = false)
    {
        $nul = 'ноль';
        $ten = array(
            array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
        );
        $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
        $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $unit = array( // Units
            array('копейка', 'копейки', 'копеек', 1),
            array('рубль', 'рубля', 'рублей', 0),
            array('тысяча', 'тысячи', 'тысяч', 1),
            array('миллион', 'миллиона', 'миллионов', 0),
            array('миллиард', 'милиарда', 'миллиардов', 0),
        );
        //
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = array();
        if ($dogovor) {
            $out[] = '(';
        }
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!intval($v)) continue;
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
                else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1) $out[] = self::morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            } //foreach
        } else $out[] = $nul;
        if ($dogovor) {
            $out[] = ')';
        }
        $out[] = self::morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
        $out[] = $kop . ' ' . self::morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
        $res = trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
        if ($dogovor) {
            $res = str_replace(['( ', ' )'], ['(', ')'], $res);
        }

        return $res;
    }

    static function morph($n, $f1, $f2, $f5)
    {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5) return $f2;
        if ($n == 1) return $f1;
        return $f5;
    }

    static function ucfirst($str)
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
    }

}
