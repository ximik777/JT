<?php

namespace JT\Core;

// test

class MemcacheSocketServer
{
    var $error_number;
    var $error;
    private $socket;

    var $clients = array();
    var $on = array();
    var $debug_enable = true;

    private $commands = [
        'storage' => ['set', 'add', 'replace'],
        'retrieval' => ['get'],
        'deleted' => ['delete'],
        'increment/decrement' => ['incr', 'decr'],
        'version' => ['version'],
        'flush_all' => ['flush_all'],
        'stats' => ['stats'],
    ];

    var $version = 'JT/MemcacheSocketServer';

    const TYPE_STRING = 0;
    const TYPE_SERIALIZE = 1;
    const TYPE_BOOL = 256;
    const TYPE_INT = 768;
    const TYPE_DOUBLE = 1792;

    var $types = array(
        'string' => self::TYPE_STRING,
        'NULL' => self::TYPE_SERIALIZE,
        'array' => self::TYPE_SERIALIZE,
        'object' => self::TYPE_SERIALIZE,
        'boolean' => self::TYPE_BOOL,
        'integer' => self::TYPE_INT,
        'double' => self::TYPE_DOUBLE
    );

    public function __construct($host, $port)
    {
        $this->socket = stream_socket_server("tcp://{$host}:{$port}", $this->error_number, $this->error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

        if (!$this->socket) {
            $this->debug("Error: [{$this->error_number}][{$this->error}]\n");
        }

        $this->debug("Listing socket on tcp://{$host}:{$port}\n");
    }

    public function listen()
    {
        while (true) {
            //формируем массив прослушиваемых сокетов:
            $read = $this->clients;
            $read [] = $this->socket;
            $write = $except = null;

            if (!stream_select($read, $write, $except, null)) {//ожидаем сокеты доступные для чтения (без таймаута)
                break;
            }

            if (in_array($this->socket, $read)) {//есть новое соединение
                //принимаем новое соединение и производим рукопожатие:
                $name = null;
                if ($connect = stream_socket_accept($this->socket, -1, $name)) {
                    $this->clients[] = $connect;//добавляем его в список необходимых для обработки
                    $this->debug("[{$name}] client connected.\n");

                    if (isset($this->on['connect'])) {
                        $this->on['connect']($connect, $name);
                    }
                }
                unset($read[array_search($this->socket, $read)]);
            }

            foreach ($read as $connect) {//обрабатываем все соединения
                $content = fread($connect, 8192);
                $name = stream_socket_get_name($connect, true);

                if (!$content) { //соединение было закрыто
                    if (isset($this->on['disconnect'])) {
                        $this->on['disconnect']($connect, $name);
                    }
                    $this->debug("[{$name}] client disconnected.\n");
                    fclose($connect);
                    unset($this->clients[array_search($connect, $this->clients)]);
                    continue;
                }

                $data = explode("\r\n", $content, 2);
                $param = explode(' ', trim($data[0]));
                $command = array_shift($param);
                $result = '';


                if (in_array($command, $this->commands['storage'])) {

                    $result = $this->_Storage($command, $param[0], substr($data[1], 0, $param[3]), intval($param[1]), $param[2], $param[3]);

                } else if (in_array($command, $this->commands['retrieval'])) {

                    $result = $this->_Retrieval($command, $param, (count($param) > 1));

                } elseif (in_array($command, $this->commands['increment/decrement'])) {

                    $result = $this->_Increment_Decrement($command, $param[0], $param[1]);

                } elseif (in_array($command, $this->commands['deleted'])) {

                    $result = $this->_Deleted($command, $param[0]);

                } elseif (in_array($command, $this->commands['version'])) {

                    $result = $this->_Version($command);

                } elseif (in_array($command, $this->commands['flush_all'])) {

                    $result = $this->_FlushAll($command);

                } elseif (in_array($command, $this->commands['stats'])) {

                    $result = $this->_stats($command);

                }

                $result = $result."\r\n";
                fwrite($connect, $result, strlen($result));
            }
        }
    }

    private function get_flag($variable)
    {
        $type = gettype($variable);
        if (in_array($type, array_keys($this->types))) {
            return $this->types[$type];
        }
        return 0;
    }

    private function buildRetrievalResponse($key, $result)
    {
        $flag = $this->get_flag($result);

        if ($flag === self::TYPE_STRING) {
            $result = strval($result);
        } elseif ($flag === self::TYPE_INT || $flag === self::TYPE_BOOL) {
            $result = intval($result);
        } elseif($flag === self::TYPE_SERIALIZE) {
            $result = serialize($result);
        }

        $len = strlen($result);
        return "VALUE {$key} {$flag} {$len}\r\n{$result}\r\n";
    }

    private function debug($text = '')
    {
        if (!$this->debug_enable || empty($text))
            return;
        echo $text;
    }

    public function event($event_name, Closure $func)
    {
        $this->on[$event_name] = $func;
    }

    private function _Storage($command, $key, $value, $flag, $expires, $bytes)
    {
        if ($flag === self::TYPE_STRING) {
            $value = strval($value);
        } elseif ($flag === self::TYPE_INT || $flag === self::TYPE_BOOL) {
            $value = intval($value);
        } elseif($flag === self::TYPE_SERIALIZE) {
            $value = serialize($value);
        }

        $return = false;
        if (isset($this->on[$command])) {
            $return = $this->on[$command]($key, $value, $flag, $expires, $bytes);
        }

        return $return ? "STORED" : "NOT_STORED";
    }

    private function _Retrieval($command, $keys, $multi = false)
    {
        $return = false;
        $command = !$multi ? $command : 'gets';

        if (isset($this->on[$command])) {
            $return = $this->on[$command]($keys);
        }

        $response = '';
        if ($multi) {
            foreach ($keys as $k => $key) {
                if (isset($result[$key])) {
                    $response .= $this->buildRetrievalResponse($key, $result[$key]);
                }
            }
        } else {
            $response .= $this->buildRetrievalResponse($keys, $return);
        }
        $response .= "END";

        return $response;
    }

    private function _Increment_Decrement($command, $key, $value)
    {
        $return = false;

        if (isset($this->on[$command])) {
            $return = $this->on[$command]($key, intval($value));
        }

        return is_int($return) ? $return : "NOT_FOUND";
    }

    private function _Deleted($command, $key)
    {
        $return = false;
        if (isset($this->on[$command])) {
            $return = $this->on[$command]($key);
        }
        return $return ? "DELETED" : "NOT_FOUND";
    }

    private function _Version($command)
    {
        $return = $this->version;
        if (isset($this->on[$command])) {
            $return = $this->on[$command]();
        }
        return "VERSION " . (strval($return));
    }

    private function _FlushAll($command)
    {
        if (isset($this->on[$command])) {
            $this->on[$command]();
        }
        return "OK";
    }

    private function _stats($command)
    {
        $return = '';
        if (isset($this->on[$command])) {
            $return = $this->on[$command]();
        }

        $return = strval($return) . "\r\nEND";

        return $return;
    }

    public function __destroy()
    {
        @fclose($this->socket);
    }
}




//{
//    $m = new MemcacheSocketServer('127.0.0.1', 1234);
//
//    $m->event('get', function(){
//
//    });
//}



//
//
//
//$m->onGet(function () {
//    return 'ololo';
//});
//
//$m->onGets(function ($keys) {
//
//    return false;
//
//});
//
//
//$m->listen();

