<?php

namespace JT\Core;

class Json
{
    private $data = array();
    private $json = '{}';
    private $const = 0;

    private $callback = false;
    private $pretty = false;
    private $cyrillic = false;
    private $example = false;

    public function __construct($dataArray = array(), $const = 0)
    {
        $this->data = $dataArray;
        $this->const = $const;
    }

    public function setCallback($callback = 'callback')
    {
        $this->callback = $callback;
        return $this;
    }

    public function setPretty($pretty = false)
    {
        $this->pretty = $pretty;
        return $this;
    }

    public function setCyrillic($cyrillic = false)
    {
        $this->cyrillic = $cyrillic;
        return $this;
    }

    public function setExample($example = false)
    {
        $this->example = $example;
        return $this;
    }

    public function setData($data = array())
    {

    }

    public function __toString()
    {

        if ($this->cyrillic) {
            $this->const |= JSON_UNESCAPED_UNICODE;
        }

        if ($this->pretty) {
            $this->const |= JSON_PRETTY_PRINT;
        }

        if ($this->example) {
            $this->const |= JSON_UNESCAPED_SLASHES;
        }

        $this->json = json_encode($this->data, $this->const);

        if ($this->callback) {
            $this->json = $this->callback . '(' . $this->json . ');';
        }

        if ($this->example) {
            $this->json = str_replace("\n", "\\n", addslashes($this->json));
        }

        return $this->json;
    }

    private function unicode2cyrillic($string)
    {
        $chars = array(
            '\u0430' => 'а', '\u0410' => 'А',
            '\u0431' => 'б', '\u0411' => 'Б',
            '\u0432' => 'в', '\u0412' => 'В',
            '\u0433' => 'г', '\u0413' => 'Г',
            '\u0434' => 'д', '\u0414' => 'Д',
            '\u0435' => 'е', '\u0415' => 'Е',
            '\u0451' => 'ё', '\u0401' => 'Ё',
            '\u0436' => 'ж', '\u0416' => 'Ж',
            '\u0437' => 'з', '\u0417' => 'З',
            '\u0438' => 'и', '\u0418' => 'И',
            '\u0439' => 'й', '\u0419' => 'Й',
            '\u043a' => 'к', '\u041a' => 'К',
            '\u043b' => 'л', '\u041b' => 'Л',
            '\u043c' => 'м', '\u041c' => 'М',
            '\u043d' => 'н', '\u041d' => 'Н',
            '\u043e' => 'о', '\u041e' => 'О',
            '\u043f' => 'п', '\u041f' => 'П',
            '\u0440' => 'р', '\u0420' => 'Р',
            '\u0441' => 'с', '\u0421' => 'С',
            '\u0442' => 'т', '\u0422' => 'Т',
            '\u0443' => 'у', '\u0423' => 'У',
            '\u0444' => 'ф', '\u0424' => 'Ф',
            '\u0445' => 'х', '\u0425' => 'Х',
            '\u0446' => 'ц', '\u0426' => 'Ц',
            '\u0447' => 'ч', '\u0427' => 'Ч',
            '\u0448' => 'ш', '\u0428' => 'Ш',
            '\u0449' => 'щ', '\u0429' => 'Щ',
            '\u044a' => 'ъ', '\u042a' => 'Ъ',
            '\u044b' => 'ы', '\u042b' => 'Ы',
            '\u044c' => 'ь', '\u042c' => 'Ь',
            '\u044d' => 'э', '\u042d' => 'Э',
            '\u044e' => 'ю', '\u042e' => 'Ю',
            '\u044f' => 'я', '\u042f' => 'Я'
        );

        return str_replace(array_keys($chars), array_values($chars), $string);
    }


}