<?php

namespace JT\Core\Captcha;

use JT\Core\Config;

class Captcha
{
    var $image;
    var $code;

    var $config = array(
        'width' => 139,
        'height' => 22,
        'font_size' => 13,
        'noise' => 3,
        'fonts_path' => './Fonts/',
        'fonts' => array(
            'arial.ttf',
            'times.ttf',
            'georgia.ttf',
            'verdana.ttf',
            'actionman.ttf',
            'oldnewspaper.ttf'
        ),
        'letter' => 'WERTYUIPASDFGHJKLZXCVBNM123456789'
    );

    function __construct($config = null)
    {
        if(is_string($config)){
            $config = Config::get($config, []);
        }

        if(!is_array($config)){
            $config = [];
        }

        $this->config['fonts_path'] = __DIR__ . '/' . $this->config['fonts_path'];

        $this->config = array_merge($this->config, $config);

        $this->config['fonts_path'] = realpath($this->config['fonts_path']);
    }

    function rand_color($min = 255, $max = 255)
    {
        $rgb = array(
            0 => mt_rand($min, $max),
            1 => mt_rand($min, $max),
            2 => mt_rand($min, $max)
        );
        return imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]);
    }

    function create_noise()
    {
        for ($i = 0; $i < $this->config['noise']; $i++) {
            $color = $this->rand_color(100, 225);
            imageline($this->image, mt_rand(0, $this->config['width']), mt_rand(0, $this->config['height']), mt_rand(0, $this->config['width']), mt_rand(0, $this->config['height']), $color);
            for ($j = 0; $j < 2; $j++) {
                $color = $this->rand_color(0, 255);
                imagesetpixel($this->image, mt_rand(0, $this->config['width']), mt_rand(0, $this->config['height']), $color);
            }
        }
        return true;
    }

    function rand_text()
    {
        $this->code = '';
        for ($i = 0; $i < 5; $i++) {
            $t = $this->config['letter'][mt_rand(0, strlen($this->config['letter']) - 1)];
            $this->code .= $t;
            imagettftext($this->image, mt_rand($this->config['font_size'] - 1, $this->config['font_size'] + 2), mt_rand(-35, 35), mt_rand(0, 30) + $i * ($this->config['width'] / 5), $this->config['height'] / 2 + $this->config['font_size'] / 2, $this->rand_color(50, 200), $this->config['fonts_path'] . '/' . $this->config['fonts'][mt_rand(0, count($this->config['fonts']) - 1)], $t);
        }
        return true;
    }

    function create_wave()
    {
        $imageNew = imagecreate($this->config['width'], $this->config['height']);
        imagepalettecopy($imageNew, $this->image);
        $movw = array(
            -1 => 0
        );
        $movh = 0;
        for ($w = 0; $w < $this->config['width']; $w++) {
            if ($w % 4 == 0)
                $movh += rand(-1, 1);
            for ($h = 0; $h < $this->config['height']; $h++) {
                $mh = $h - $movh;
                if ($mh < 0)
                    $mh = 0;
                elseif ($mh >= $this->config['height'])
                    $mh = $this->config['height'] - 1;
                if (!isset($movw[$h])) {
                    $movw[$h] = $movw[$h - 1] + ($h % 4 == 0 ? rand(-1, 1) : 0);
                }
                $mw = $w - $movw[$h];
                if ($mw < 0)
                    $mw = 0;
                elseif ($mw >= $this->config['width'])
                    $mw = $this->config['width'] - 1;
                $c = imagecolorat($this->image, $mw, $mh);
                imagesetpixel($imageNew, $w, $h, $c);
            }
        }
        $this->image = $imageNew;
    }

    function create()
    {
        $this->image = imagecreate($this->config['width'], $this->config['height']);
        $this->rand_color(255, 255);
        $this->create_noise();
        $this->rand_text();
        return $this->code;
    }

    function show()
    {
        header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");
        header("Cache-Control: no-cache");
        header("Content-type: image/png");
        imagepng($this->image);
        imagedestroy($this->image);
        exit();
    }
}
