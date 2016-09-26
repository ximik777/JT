<?php

namespace JT\Core\MySQLi;

class MySQLiStatic
{
    protected static $instance;

    protected function __clone()
    {
    }

//    private function __construct($config)
//    {
//        self::$instance = new MySQLi($config);
//    }

    public static function dump_sql()
    {
        return self::$instance->dump_sql = true;
    }

    public static function init($config = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new MySQLi($config);
        }
        return self::$instance;
    }

    public static function getHandle()
    {
        return self::$instance->handle;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public static function get_last_error()
    {
        return self::$instance->error;
    }

    public static function get_last_errno()
    {
        return self::$instance->errno;
    }

    public static function get_sql()
    {
        return self::$instance->sql;
    }

    public static function ping(){
        return self::$instance->ping();
    }

    public static function query_replace($sql, $data_arr = null)
    {
        return self::$instance->query_replace($sql, $data_arr);
    }

    public static function query($sql, $data_arr = null)
    {
        return self::$instance->query($sql, $data_arr);
    }

    public static function query_insert($sql, $data_arr = null)
    {
        return self::$instance->query_insert($sql, $data_arr);
    }

    public static function query_affected_rows($sql, $data_arr = null)
    {
        return self::$instance->query_affected_rows($sql, $data_arr);
    }

    public static function get_value_query($sql, $data_arr = null)
    {
        return self::$instance->get_value_query($sql, $data_arr);
    }

    public static function get_array_list($sql, $data_arr = null)
    {
        return self::$instance->get_array_list($sql, $data_arr);
    }

    public static function get_key_val_array($sql, $data_arr = null)
    {
        return self::$instance->get_key_val_array($sql, $data_arr);
    }

    public static function get_affected_rows($sql, $data_arr = null)
    {
        return self::$instance->get_affected_rows($sql, $data_arr);
    }

    public static function get_one_line_assoc($sql, $data_arr = null)
    {
        return self::$instance->get_one_line_assoc($sql, $data_arr);
    }

    public static function exec_query($query, $transaction = false)
    {
        return self::$instance->exec_query($query, $transaction);
    }

    public static function get_assoc_column($sql, $data_arr = null)
    {
        return self::$instance->get_assoc_column($sql, $data_arr);
    }

    public static function get_assoc_column_id($sql, $data_arr = null)
    {
        return self::$instance->get_assoc_column_id($sql, $data_arr);
    }

    public static function begin()
    {
        return self::$instance->begin();
    }

    public static function transaction_start()
    {
        return self::$instance->transaction_start();
    }

    public static function commit()
    {
        return self::$instance->commit();
    }

    public static function rollback()
    {
        return self::$instance->rollback();
    }

    public function __destruct()
    {
        if (self::$instance->handle)
            self::$instance->handle->close();
    }
}
