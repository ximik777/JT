<?php

namespace JT\Core;

class SocketServer
{

    private $config = array(
        'protocol' => 'sslv3',
        'listen' => '0.0.0.0',
        'port' => 1234,
        'max_byte' => 8192,
        'context' => array()
    );

    var $listen = '';
    var $context = array();
    var $errno;
    var $error;
    var $socket;

    var $clients = array();
    private $on = array();
    var $debug_enable = true;
    var $db = array();


    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);

        $this->listen = $this->config['protocol'] . '://' . $this->config['listen'] . ':' . $this->config['port'];

        $this->config['max_byte'] = $this->config['max_byte'] > 8192 ? 8192 : $this->config['max_byte'];

        $this->socket = @stream_socket_server(
            $this->listen,
            $this->errno,
            $this->error,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            stream_context_create(
                $this->config['context']
            )
        );

        if (!$this->socket) {
            $this->debug("Error: [{$this->errno}][{$this->error}]\n");
            return;
        }

        $this->debug("Listing socket on {$this->listen}\n");
    }

    public function listen()
    {
        if(!$this->socket){
            $this->debug("The socket is not created.\n");
            return;
        }

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

                        $this->db[$name] = array();

                        $return = $this->on['connect']($connect, $name, $this->db[$name]);
                        if (is_string($return)) {
                            $len = sprintf("% 20d", strlen($return));
                            fwrite($connect, $len . $return);
                            $this->debug("[{$name}] server response: {$return}\n");
                        }
                    }
                }
                unset($read[array_search($this->socket, $read)]);
            }

            foreach ($read as $connect) {//обрабатываем все соединения
                $data = fread($connect, $this->config['max_byte']);
                $name = stream_socket_get_name($connect, true);

                if (!$data) { //соединение было закрыто
                    $this->disconnect($connect, $name);
                    continue;
                }

                $return = NULL;
                $data = trim($data, " \t\r\n");
                $exp = explode("\r\n", $data, 2);
                $content = '';
                $method = '';
                if(isset($exp[0])){
                    $method = $exp[0];
                }
                if(isset($exp[1])){
                    $content = $exp[1];
                }
                if (isset($this->on[$method])) {
                    $this->debug("[{$name}] client request: [{$method}], Data: [{$content}]\n");
                    $return = $this->on[$method]($connect, $content, $name, $this->db[$name]);
                } elseif (isset($this->on['message'])) {
                    $this->debug("[{$name}] client request data: [{$data}]\n");
                    $return = $this->on['message']($connect, $data, $name, $this->db[$name]);
                } else {

                }

                if (is_string($return)) {
                    $len = sprintf("% 20d", strlen($return));
                    fwrite($connect, $len . $return);
                    $this->debug("[{$name}] server response: {$return}\n");
                }
            }
        }
    }

    public function disconnect($connect, $name)
    {
        if (isset($this->on['disconnect'])) {
            $return = $this->on['disconnect']($connect, $name, $this->db[$name]);
            if (is_string($return)) {
                $len = sprintf("% 20d", strlen($return));
                fwrite($connect, $len . $return);
                $this->debug("[{$name}] server response: {$return}\n");
            }
        }
        $this->debug("[{$name}] client disconnected.\n");
        fclose($connect);
        unset($this->db[$name]);
        unset($this->clients[array_search($connect, $this->clients)]);
    }

    private function debug($text = '')
    {
        if (!$this->debug_enable || empty($text))
            return;
        echo '['.date('Y-m-d H:i:s') . '] '.$text;
    }

    public function on($eventName = '', \Closure $func)
    {

        if (isset($this->on[$eventName])) {
            throw new \Exception("Event {$eventName} is exist", E_USER_WARNING);
        }

        $this->on[$eventName] = $func;
    }

    public function onConnect(\Closure $func)
    {
        $this->on['connect'] = $func;
    }

    public function onDisconnect(\Closure $func)
    {
        $this->on['disconnect'] = $func;
    }

    public function onMessage(\Closure $func)
    {
        $this->on['message'] = $func;
    }

    private function destroy()
    {
        @fclose($this->socket);
    }

    public function __destroy()
    {
        $this->destroy();
    }
}


