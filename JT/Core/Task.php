<?php

namespace JT\Core;

use JT\Core\MySQLi\MySQLiStatic AS db;

class Task
{
    private $config = [
        'database' => '',
        'table' => ''
    ];

    private $command = [];

    function __construct($config = [])
    {
        $this->config = (object)array_merge($this->config, $config);
    }

    function addTaskListener($type, $callback)
    {
        $this->command[$type] = $callback;
    }

    public static function add($type, $object_id, array $data, $delay = 0)
    {
        $data = json_encode($data);
        if ($res = db::get_value_query("SELECT `data` FROM `billing`.`event` WHERE `type`=$ AND `object_id`=$ AND `date_processed` IS NULL ORDER BY `id` DESC LIMIT 1", [$type, $object_id])) {
            if ($res === $data) {
                return true;
            }
        }

        return db::query_insert("INSERT INTO `billing`.`event` (`type`,`object_id`,`data`,`date_start`) VALUES ($,$,$,DATE_ADD(NOW(), INTERVAL $ SECOND))", [$type, $object_id, $data, $delay]);
    }

    function deleteTask($task_id)
    {
        return db::query("UPDATE `billing`.`event` SET `count_try`=`count_try`+1, `date_processed`=NOW() WHERE `id`=$ AND `date_processed` IS NULL LIMIT 1", $task_id);
    }

    function delayQueue($type, $object_id, $delay = 0)
    {
        return db::query("UPDATE `billing`.`event` SET `count_try`=`count_try`+1, `date_start`=DATE_ADD(NOW(), INTERVAL {$delay} SECOND) WHERE `type`=$ AND `object_id`=$ AND `date_processed` IS NULL", [$type, $object_id]);
    }

    function deleteQueue($type, $object_id)
    {
        return db::query("UPDATE `billing`.`event` SET `date_processed`=NOW() WHERE `type`=$ AND `object_id`=$ AND `date_processed` IS NULL", [$type, $object_id]);
    }

    function _getQueue()
    {
        $sql = "SELECT
                      `type`,
                      `object_id`
                    FROM
                      `billing`.`event`
                    WHERE
                      (`date_start` < NOW() OR `date_start` IS NULL) AND
                      `date_processed` IS NULL
                    GROUP BY
                      `type`, `object_id`
                    ORDER BY
                      `type` ASC";

        return db::get_array_list($sql);
    }

    function _getTaskQueue($type, $object_id)
    {
        $sql = "SELECT
                      *
                    FROM
                      `billing`.`event`
                    WHERE
                      `type` = $ AND
                      `object_id` = $ AND
                      (`date_start` < NOW() OR `date_start` IS NULL) AND
                      `date_processed` IS NULL;";

        return db::get_array_list($sql, [$type, $object_id]);
    }

    function listen()
    {
        //while (true) {

            if (!$queue = $this->_getQueue()) {
            //    sleep(1);
            //    continue;

                die();
            }

            foreach ($queue AS $task) {

                if ($taskQueue = $this->_getTaskQueue($task['type'], $task['object_id'])) {

                    if (!isset($this->command[$task['type']])) {
                        // todo add debug
                        continue;
                    }

                    foreach ($taskQueue AS $event) {

                        $event['data'] = json_decode($event['data'], true);
                        $result = $this->command[$task['type']]($event);

                        if ($result === true) {
                            if ($this->deleteTask($event['id'])) {
                                continue;
                            } else {
                                break;
                            }
                        }

                        if (is_int($result)) {
                            $this->delayQueue($task['type'], $event['object_id'], $result);
                            break;
                        }

                        $this->delayQueue($task['type'], $event['object_id'], 5);
                        break;

                    }
                }
            }

        //}
    }
}