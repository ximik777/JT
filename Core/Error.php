<?php


namespace JT\Core;

class Error
{
    static $callback;
    static $error_list = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error'
    ];

    static $display = '
    <div style="border:2px solid #F00; padding:3px; font-size:10px; %s">
    %s <b>[%s]</b><br>
    %s<br />[%s] - %s
    </div>';

    public static function Register($callback = null)
    {
        set_error_handler([__CLASS__, 'error_handler'], E_ALL);
        set_exception_handler([__CLASS__, 'exception_handler']);
        register_shutdown_function([__CLASS__, 'shutdown_handler']);

        self::setCallback($callback);
    }

    public static function setCallback($callback)
    {
        if (is_callable($callback)) {
            self::$callback = $callback;
        }
    }

    public static function error_handler($code, $message, $file, $line)
    {
        $errType = isset(self::$error_list[$code]) ? self::$error_list[$code] : "Unknown error";

        if (is_callable(self::$callback)) {
            call_user_func(self::$callback, $errType, $code, $message, $line, $file);
        } else {
            printf(self::$display,
                in_array($code, [E_NOTICE, E_USER_NOTICE]) ? 'color:#F00; background:#FFE5E5' : 'color:#F00; background:#FFE5E5',
                $errType,
                $code,
                $message,
                $line,
                $file
            );
        }

        return true;
    }

    public static function exception_handler(\Error $e)
    {
        self::error_handler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }

    public static function shutdown_handler()
    {
        $error = error_get_last();
        if ($error && (in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR]))) {
            if (strpos($error['message'], 'Allowed memory size') === 0) {
                ini_set('memory_limit', (intval(ini_get('memory_limit')) + 64) . "M");
            }
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}