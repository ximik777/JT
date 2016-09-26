<?php

namespace JT\Core;

class Letters
{
    private $config = [
        'host' => 'localhost',
        'port' => 11251,
        'server_number' => 1,
        'debug' => true
    ];

    private $handle;
    private $command = [];

    function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->handle = new \Memcache();
        $this->handle->connect($this->config['host'], $this->config['port']);
    }

    function addEvent($action, array $param, $delay = 0, $priority = 1, $task_id = 0)
    {
        $delay = $delay < 0 ? 0 : $delay > 3600 ? 3600 : $delay;
        $priority = $priority < 1 ? 1 : $priority > 9 ? 9 : $priority;

        $letter = [
            'priority' => $priority,
            'action' => $action,
            'param' => json_encode($param)
        ];

        $key = "letter{$this->config['server_number']},{$delay},{$task_id}";

        if (!$this->handle->set($key, $letter)) {
            $this->_debug("Error add event: $key");
            return false;
        }

        return true;
    }

    function deleteEvent($id)
    {
        if (is_array($id))
            $id = $id['id'];

        if (!$this->handle->delete("letter{$id}")) {
            $this->_debug("Event `{$id}` error deleted");
            return false;
        }

        $this->_debug("Event `{$id}` success deleted");
        return true;
    }

    function recoveryEvent($param, $delay = 60, $priority = null)
    {
        if ($priority) {
            $priority = $priority < 1 ? 1 : $priority > 9 ? 9 : $priority;
        } else {
            $priority = $param['priority'];
        }

        $id = $param['id'];

        if (!$this->handle->set("letter_priority{$id},{$priority},{$delay}", 'Error')) {
            $this->_debug("Event `{$id}` error recovery");
            return false;
        }

        $this->_debug("Event `{$id}` success recovery");
        return false;
    }

    function clearPriority($priority = null)
    {

        if (!$priority) {
            return false;
        }

        $priority = $priority < 1 ? 1 : $priority > 9 ? 9 : $priority;

        return $this->handle->get("clear_queue{$priority}");
    }

    function clearGroup($task_id)
    {
        return $this->handle->delete("letters_by_task_id{$task_id}");
    }

    function addEventListener($action, $callback)
    {
        $this->command[$action] = $callback;
    }

    function listen($min_priority = 1, $max_priority = 9, $cnt = 10, $immediate = false)
    {
        $this->__lock();

        $command = $immediate ? '_immediate' : '';
        $key = "letters{$command}{$this->config['server_number']},{$min_priority},{$max_priority}#{$cnt}";

        while (true) {

            if (!$letter = $this->handle->get($key)) {
                sleep(1);
                continue;
            }

            foreach ($letter as $k => $v) {

                if (!isset($v['action'])) {
                    continue;
                }

                if (!isset($this->command[$v['action']])) {
                    $this->_debug("Command `{$v['action']}` is not found");
                    continue;
                }

                if (isset($v['param'])) {
                    $v['param'] = json_decode($v['param'], true);
                }

                $this->command[$v['action']]($v);
            }
        }
    }

    function __lock()
    {
        $lock_file = sys_get_temp_dir();
        $lock_file .= '/';
        $lock_file .= md5($this->config['host'].$this->config['port']).'.lock';

        $fp = fopen($lock_file, "w+");

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $this->_debug("Another script running");
            die();
        }

        fwrite($fp, date('Y-m-d H:i:s'));
    }

    function _debug($msg)
    {
        if (!$this->config['debug'] || !$msg) return;
        echo "{$msg}\r\n";
    }

}