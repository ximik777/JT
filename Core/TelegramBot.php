<?php

namespace JT\Core;

class TelegramBot
{
    var $commands = array();
    var $input = null;

    var $config = array(
        'api_url' => 'https://api.telegram.org',
        'api_key' => '',
        'bot_name' => '',
        'webhook_url' => 'https://example.com/path_to_your.script',
        'offset' => 0,
        'limit' => 100,
        'timeout' => 30,
        'auto_answer' => true,
        'command_not_found' => "Команда '/%s' не поддерживается. 🙁",
        'bot_description' => 'Hi @%s, I\'m @%s - and I\'m a robot! 🤖',
        'help_prefix' => "You can control me by sending these commands:\r\n\r\n"
    );

    function __construct($config = array())
    {
        $this->config = (Object)array_merge($this->config, $config);
    }


    private function request($data = array(), $file = false)
    {
        if (!$data['method'])
            return false;

        $method = $data['method'];
        unset($data['method']);

        $url = ($file ? '/file' : '')."/bot{$this->config->api_key}/{$method}" . (!empty($data) ? '?' . http_build_query($data) : '');

        if($file) {
            return $this->config->api_url . $url;
        }

        $json = file_get_contents($this->config->api_url . $url);

        if ($json && $json = json_decode($json)) {
            return $json;
        }

        return false;
    }

    public function getMe()
    {
        return $this->request(['method' => 'getMe']);
    }

    public function installWebHook($url = null)
    {
        if (!$url)
            $url = $this->config->webhook_url;

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        $result = $this->request(['method' => 'setWebhook', 'url' => $url]);

        return isset($result->ok) && $result->ok == 1;
    }

    public function uninstallWebHook()
    {
        $result = $this->request(['method' => 'setWebhook', 'url' => '']);

        return isset($result->ok) && $result->ok == 1;
    }

    public function addEventListener($command = '', $description = '', callable $callback, $private = false)
    {
        $command = trim($command, '/\\');
        $this->commands[$command] = [
            'description' => $description,
            'callback' => $callback,
            'private' => $private
        ];
    }

    public function triggerEvent($command = '', \stdClass $data)
    {
        if (!isset($this->commands[$command])) {
            return false;
        }
        $result = $this->commands[$command]['callback']($data, $this);

        if ($this->config->auto_answer && is_string($result)) {
            $this->sendMessage($data->chat->id, $result);
        }

        return true;
    }

    public function sendMessage($chat_id, $text = '')
    {
        $chat_id = is_object($chat_id) ? $chat_id->chat->id : $chat_id;
        if (!$chat_id || !$text) return false;

        $data = ['method' => 'sendMessage', 'chat_id' => $chat_id, 'text' => $text];

        if ($this->input) {
            echo json_encode($data);
            die();
        }

        return $this->request($data);
    }

    public function sendSticker($chat_id, $sticker = '')
    {
        $chat_id = is_object($chat_id) ? $chat_id->chat->id : $chat_id;
        if (!$chat_id || !$sticker) return false;

        $data = ['method' => 'sendSticker', 'chat_id' => $chat_id, 'sticker' => $sticker];

        if ($this->input) {
            echo json_encode($data);
            die();
        }

        return $this->request($data);
    }

    public function getUpdates($offset = 0, $limit = 100, $timeout = 0)
    {
        return $this->request(['method' => 'getUpdates', 'offset' => $offset, 'limit' => $limit, 'timeout' => $timeout]);
    }

    public function process($data)
    {
        $this->config->offset = intval($data->update_id) + 1;

        $data->message = $data->message ? $data->message : $data->edited_message;

        if (isset($data->message->text)) {
            if (substr($data->message->text, 0, 1) == '/') {
                $message = explode(' ', $data->message->text);
                $command = trim(array_shift($message), '/\\');
                $command = explode('@', $command);
                $data->message->text = implode(' ', $message);

                if (!$this->triggerEvent($command[0], $data->message)) {

                    $this->sendMessage($data->message->chat->id, sprintf($this->config->command_not_found, $command[0]));
                }
            } else {

                $this->triggerEvent('text', $data->message);

            }
        } else {
            $this->triggerEvent('other', $data->message);
        }
    }

    public function input($input = "php://input")
    {
        $this->input = true;

        header("Content-type: application/json; charset=utf8");

        if ($content = file_get_contents($input)) {
            if ($json = json_decode($content)) {
                $this->process($json);
            }
        }
    }


    public function listen()
    {
        if (!$data = $this->getUpdates($this->config->offset, $this->config->limit, $this->config->timeout)) {
            sleep(10);
        }

        if ($data->ok && !empty($data->result)) {
            foreach ($data->result as $k => $v) {
                $this->process($v);
            }
        }

        $this->listen();
    }

    public function defaultHelp()
    {

        $text = $this->config->help_prefix;

        foreach ($this->commands as $k => $v) {
            if ($v['private'])
                continue;
            $text .= '/' . $k . ' - ' . $v['description'] . "\r\n";
        }

        return $text;
    }

    public function getUserAvatar($tg_id) {

        if(!$photos = $this->request(['method' => 'getUserProfilePhotos', 'user_id' => $tg_id])) {
            return false;
        }

        if(!$file_id = $photos->result->photos[0][0]->file_id) {
            return false;
        }

        if(!$tmp_path = $this->request(['method' => 'getFile', 'file_id' => $file_id])) {
            return false;
        }

        if(!$path = $tmp_path->result->file_path) {
            return false;
        }

        return $this->request(['method' => $path], true);
    }


}