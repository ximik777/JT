<?php

namespace JT\Core;

class Docx
{
    var $tmp_dir = '/tmp/';
    private $tmp_files = '';
    private $files = array();
    private $dirs = array();
    private $content = '';

    static function countPages($file)
    {
        if (!is_file($file)) {
            return false;
        }

        if (!$app = (array)simplexml_load_file("zip://{$file}#docProps/app.xml")) {
            return false;
        }

        return intval(!$app['Pages'] ? 0 : $app['Pages']);
    }

    public static function getVariables($file)
    {
        if (!is_file($file)) {
            return false;
        }

        if (!$content = file_get_contents("zip://{$file}#word/document.xml")) {
            return false;
        }

        if (!preg_match_all('/#\[([A-Z0-9_]+)\]#/', $content, $data)) {
            return false;
        }

        $data[1] = array_values(array_unique($data[1]));
        sort($data[1]);

        return $data[1];
    }

    function __construct($path)
    {

        if (!is_file($path)) {
            throw new \Exception("Docx file {$path} is not exist");
        }

        $this->tmp_dir = sys_get_temp_dir() . '/';
        $this->tmp_files = $this->tmp_dir;
        $this->tmp_dir = $this->tmp_dir . (md5(time() . mt_rand())) . '/';

        $zip = new \ZipArchive;
        if ($res = $zip->open($path)) {
            $zip->extractTo($this->tmp_dir);
        } else {
            return false;
        }

        $count = $zip->numFiles;
        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            $this->files[] = $this->tmp_dir . $stat['name'];
            $this->dirs[] = pathinfo($this->tmp_dir . $stat['name'], PATHINFO_DIRNAME);
        }
        $zip->close();

        sort($this->dirs);
        $this->dirs = array_reverse($this->dirs);

        $this->content = file_get_contents($this->tmp_dir . 'word/document.xml');
        return true;
    }

    private function replace($key, $value)
    {
        $this->content = str_replace(("#[" . (strtoupper(trim($key))) . "]#"), trim($value), $this->content);
    }

    private function sortData($data)
    {
        $keys = array_map('strlen', array_keys($data));
        array_multisort($keys, SORT_DESC, $data);
        return $data;
    }

    function replaceVariable($data = array())
    {
        $data = $this->sortData($data);

        foreach ($data as $k => $v) {
            $this->replace($k, $v);
        }
        return true;
    }

    function replaceVariableTest($data = array())
    {
        $data = $this->sortData($data);

        foreach ($data as $k => $v) {
            $this->replace($k, '[*** ' . $v . ' ***]');
        }
        return true;
    }

    function pack()
    {
        if (!file_put_contents($this->tmp_dir . 'word/document.xml', $this->content)) {
            return false;
        }
        return true;
    }


    function save($path = '')
    {
        $this->pack();

        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::CREATE) === TRUE) {
            foreach ($this->files as $k) {
                $zip->addFile($k, str_replace($this->tmp_dir, '', $k));
            }
            $zip->close();
            $this->delete_tmp();
            return true;
        } else {
            return false;
        }
    }

    function download($name = null)
    {
        $this->pack();

        $tmp_filename = $this->tmp_files . uniqid(true) . ".docx";
        $name = basename($name ? $name : $tmp_filename);
        $this->save($tmp_filename);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmp_filename));
        ob_clean();
        flush();
        readfile($tmp_filename);
        unlink($tmp_filename);
        exit();
    }

    function delete_tmp()
    {
        foreach ($this->files as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }

        foreach ($this->dirs as $d) {
            if (is_dir($d)) {
                rmdir($d);
            }
        }
    }

    function __destruct()
    {
        $this->delete_tmp();
    }
}