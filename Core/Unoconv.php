<?php

namespace JT\Core;


/*
INSTALL
apt-get install unoconv
create script
vim /usr/local/bin/unoconv.sh
*** content ***
#!/bin/bash

if [ -z "$1" ]; then
    echo "Must pass file";
    exit 10;
fi

/usr/bin/unoconv --format=pdf --output=$1 $2
/bin/chown www-data:www-data $1
*** content end ***
sudo chmod +x /usr/local/bin/unoconv.sh

vim /etc/sudoers
add line
www-data    ALL=NOPASSWD: /usr/local/bin/unoconv.sh

*/

class Unoconv
{
    static $unoconv = '/usr/local/bin/unoconv.sh';

    public static function docx2pdf($docx_path, $pdf_path)
    {
        $input = escapeshellarg($docx_path);
        $output = escapeshellarg($pdf_path);
        $cmd = sprintf("sudo %s %s %s", self::$unoconv, $output, $input);

        exec($cmd, $error, $ret_code);
        if($ret_code !== 0){
            return false;
        }

        if(!is_file($pdf_path)){
            return false;
        }

        return true;
    }
}