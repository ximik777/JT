<?php

namespace JT\Core\MySQLi;

use JT\Core\Config;

class MySQLi
{
    var $handle;
    var $sql;
    var $error;
    var $errno;
    var $transaction = false;
    var $dump_sql = false;

    private $config = array(
        'host' => 'localhost',
        'user' => '',
        'pass' => '',
        'name' => '',
        'port' => '3306',
        'charset' => 'utf8',
        'persistent' => false,
        'autocommit' => true
    );

    function __construct($config = null)
    {
        if(is_string($config)){
            $config = Config::get($config, []);
        }

        if(!is_array($config)){
            $config = [];
        }

        $this->config = array_merge($this->config, $config);

        if ($this->config['persistent'] === true) {
            $this->config['host'] = 'p:' . $this->config['host'];
        }

        $this->handle = mysqli_init();

        if (!$this->connect()) {
            throw new \Exception("Database is not connected", E_WARNING);
        }
    }

    private function connect()
    {
        $this->handle = mysqli_init();

        if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            $this->handle->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        }

        if (!$this->handle->real_connect($this->config['host'], $this->config['user'], $this->config['pass'], $this->config['name'], $this->config['port'])) {
            $this->errno = $this->handle->errno;
            $this->error = $this->handle->errno . ' ' . $this->handle->error;
            return false;
        }

        if ($this->config['autocommit'] === true) {
            $this->handle->autocommit(true);
        }

        if ($this->config['charset'] !== false) {
            $this->handle->set_charset($this->config['charset']);
        }

        return true;
    }

    public function query_replace($sql, $data_arr = null)
    {
        if ($data_arr === null || $data_arr == array()) {
            return $sql;
        } else {
            $sql_out = '';
            $start = 0;
            preg_match_all('/([^\\\\]{1}\\$)/', $sql, $math, PREG_OFFSET_CAPTURE);

            foreach ($math[1] as $key => $val) {
                $sql_out .= substr($sql, $start, $val[1] - $start + 1);

                if (is_array($data_arr)) {
                    $sql_out .= is_null($data_arr[$key]) ? 'NULL' : "'" . addslashes($data_arr[$key]) . "'";
                } elseif ($key == 0) {
                    $sql_out .= is_null($data_arr) ? 'NULL' : "'" . addslashes($data_arr) . "'";
                }
                $start = $val[1] + 2;
            }

            $sql_out .= substr($sql, $start);
            return str_replace('\\$', '$', $sql_out);
        }
    }


    public function query($sql, $data_arr = null)
    {
        $this->sql = $this->query_replace($sql, $data_arr);
        if($this->dump_sql){
            trigger_error($this->sql, E_USER_NOTICE);
        }
        if (!$res = $this->handle->query($this->sql)) {
            $this->errno = $this->handle->errno;
            $this->error = $this->sql . ' ' . $this->handle->errno . ' ' . $this->handle->error;
            return false;
        }
        return $res;
    }

    public function ping()
    {
        if (!$this->query('SELECT LAST_INSERT_ID()')) {
            if ($this->handle->errno == 2006) {
                return $this->connect();
            }
            return false;
        }

        return true;
    }

    public function get_last_error()
    {
        return $this->handle->error;
    }

    public function get_last_errno()
    {
        return $this->handle->errno;
    }

    public function get_last_sql()
    {
        return $this->handle->errno;
    }

    public function query_insert($sql, $data_arr = null)
    {
        $this->query($sql, $data_arr);
        return $this->handle->insert_id;
    }

    public function query_affected_rows($sql, $data_arr = null)
    {
        $this->query($sql, $data_arr);
        return $this->handle->affected_rows;
    }

    public function get_value_query($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr)) return false;
        if ($res->num_rows & $res->field_count) {
            $res = $res->fetch_array();
            return $res[0];
        } else {
            return false;
        }
    }

    public function get_array_list($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr)) return false;
        $array = array();
        while ($row = $res->fetch_assoc()) {
            $array[] = $row;
        }
        return $array;
    }

    public function get_key_val_array($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr)) return false;
        $array = array();
        while ($row = $res->fetch_array()) {
            $array[$row[0]] = $row[1];
        }
        return $array;
    }

    public function get_affected_rows($sql, $data_arr = null)
    {
        $this->query($sql, $data_arr);
        return $this->handle->affected_rows;
    }

    public function get_one_line_assoc($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr))
            return false;
        return $res->fetch_assoc();
    }

    public function exec_query($query, $transaction = false)
    {
        $i = 0;
        $arr = preg_split('/;[ 	]*(\n|\r)/', trim($query));
        if ($transaction) $this->transaction_start();
        foreach ($arr as $a) {
            if (!$this->query($a)) {
                if ($transaction) {
                    $this->rollback();
                }
                return 0;
            }
            $i++;
        }
        if ($transaction) $this->commit();
        return $i;
    }

    public function get_assoc_column($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr)) return false;
        $arr = array();
        while ($row = $res->fetch_array()) {
            $arr[] = $row[0];
        }
        return $arr;
    }

    public function get_assoc_column_id($sql, $data_arr = null)
    {
        if (!$res = $this->query($sql, $data_arr)) return false;
        $arr = array();
        while ($row = $res->fetch_assoc()) {
            $id = array_shift($row);
            $arr[$id] = $row;
        }
        return $arr;
    }

    public function begin()
    {
        return $this->transaction_start();
    }

    public function transaction_start()
    {
        if($this->transaction)
            return true;

        return $this->transaction = $this->handle->begin_transaction();
    }

    public function commit()
    {
        return $this->transaction = $this->handle->commit();
    }

    public function rollback()
    {
        return $this->transaction = $this->handle->rollback();
    }

    public function __destruct()
    {
        if ($this->handle)
            $this->handle->close();
    }
}