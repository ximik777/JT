<?php


namespace JT\Core;


class Convert
{

    //convert -background white -alpha remove -density 400 Dogovor.pdf -resize 2000x1500  my_filename%d.jpg

    static function pdf2jpg($pdf, $output_dir, $filename)
    {
        $images = new \Imagick();
        $images->setResolution(150,150);
        $images->readImage('./Dogovor.pdf');

        foreach ($images as $i => $im){
            $im->setImageBackgroundColor('white');
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $im->setImageFormat('jpeg');
            $im->setImageCompression(Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality(100);
            $im->writeImage('./test/my_filename'.$i.'.jpg');
        }
        $images->clear();
        $images->destroy();
    }
}