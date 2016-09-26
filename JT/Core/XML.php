<?php
namespace JT\Core;

class XML
{
    private $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    function buildXml($data, $i = -1)
    {
        $result = [];
        $i++;
        if ($i === 0) $result[] = '<?xml version="1.0" encoding="utf-8"?>';
        foreach ($data as $k => $v) {
            $result[] = str_repeat("\t", $i) . "<" . (is_numeric($k) ? "item" : $k) . "" . (is_array($v) && $v == array_values($v) ? ' list="true"' : "") . ">" . (is_array($v) ? "\r\n" . $this->buildXml($v, $i) . "\r\n" . str_repeat("\t", $i) : $v) . "</" . (is_numeric($k) ? "item" : $k) . ">";
        }
        return implode("\r\n", $result);
    }

    function __toString()
    {
        return $this->buildXml($this->data);
    }

}