<?php


namespace JT\Core\Session;

class SessionSaveHandler //implements \SessionHandlerInterface
{
    protected $mc;

    public function __construct()
    {
        session_set_save_handler(
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        );
    }

    public function open($savePath, $sessionName)
    {
        echo "open\n";
        var_dump($savePath, $sessionName);
        $this->mc = new \Memcache();
        $this->mc->connect('127.0.0.1', 11211);
        return true;
    }

    public function close()
    {
        echo "close\n";
        $this->mc->close();
        return true;
    }

    public function read($session_id)
    {
        echo "read\n";
        var_dump($session_id);
        return $this->mc->get($session_id);
    }

    public function write($session_id, $data)
    {
        echo "write\n";
        //session_decode($data);
        var_dump($_SESSION);
        var_dump($session_id, $data);
        return $this->mc->set($session_id, $data, false, ini_get('session.gc_maxlifetime'));
    }

    public function destroy($session_id)
    {
        echo "destroy\n";
        var_dump($session_id);
        return $this->mc->delete($session_id);
    }

    public function gc($maxlifetime)
    {
    }

}