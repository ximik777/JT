<?php

namespace JT\Core;

class Mail
{
    var $mailer = 'Mime Mailer 1.0';

    var $_to = false;
    var $_to_name = false;

    var $_cc = array();

    var $_bcc = array();

    var $_from = false;
    var $_from_name = false;

    var $_reply_to = false;

    var $_subject = '';
    var $_text = false;

    var $addresses = array();

    var $error = false;

    var $headers = array();
    var $headers_text = array();

    var $charset = 'utf-8';
    var $mime_version = 'MIME-Version: 1.0';

    var $data = '';
    var $embeded = array();
    var $attache = array();
    var $emailboundary = '';
    var $http_host = false;

    var $template = array();

    var $config = array(
        'host' => '',
        'port' => '25',
        'user' => '',
        'pass' => '',
        'auth' => false,
        'ssl' => false,
        'from' => false,
        'from_name' => false
    );

    var $log = array();

    var $socket = false;

    function __construct($config = null)
    {
        if(is_string($config)){
            $config = Config::get($config, []);
        }

        if(!is_array($config)){
            $config = [];
        }

        $this->config = array_merge($this->config, $config);

        $this->mixed_boundary = str_repeat('-', 12) . substr(md5(time() - 1), 0, 25);
        $this->alt_boundary = str_repeat('-', 12) . substr(md5(time() - 2), 0, 25);
        $this->related_boundary = str_repeat('-', 12) . substr(md5(time() - 3), 0, 25);

        $this->_from = $this->config['from'] ?: $this->_from;
        $this->_from_name = $this->config['from_name'] ?: $this->_from_name;

        if(!$this->_from && $this->config['user'] != ''){
            $this->_from = $this->config['user'];
        }

        if($this->_from){
            list (, $this->http_host) = explode('@', $this->_from);
        } else {
            $this->http_host = isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        }

    }

    function to($to = false, $to_name = false)
    {
        $this->_to = $to;
        $this->_to_name = $to_name ? $to_name : false;
    }

    function reply_to($reply_to = false)
    {
        $this->_reply_to = $reply_to;
    }

    function cc($cc = false)
    {
        if (is_array($cc))
            $this->_cc = array_merge($this->_cc, $cc);
        if (is_string($cc))
            $this->_cc[] = $cc;
    }

    function Bcc($bcc = false)
    {
        if (is_array($bcc))
            $this->_bcc = array_merge($this->_bcc, $bcc);
        if (is_string($bcc))
            $this->_bcc[] = $bcc;
    }

    function from($from = false, $from_name = false)
    {
        $this->_from = $from ?: $this->_from;
        $this->_from_name = $from_name ?: $this->_from_name;
    }

    function subject($subject = '')
    {
        $this->_subject = $subject != '' ? strip_tags(trim($subject)) : $this->_subject;
    }

    function text($text = '', $subject = '')
    {
        $this->_text = $text !== '' ? $text : false;
        $this->subject($subject);
    }

    function minify_html($buffer)
    {
        return preg_replace(array(
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s'
        ), array(
            '>',
            '<',
            '\\1'
        ), $buffer);
    }

    function embed_images($matches)
    {
        if (strstr($matches[1], 'cid:')) return $matches[0];
        return 'src="' . ($this->embed($matches[1])) . '"';
    }

    function html($content = '', $ei = false)
    {
        if ($content == '') return false;
        $this->_text = $this->minify_html($content);

        if ($ei) {
            $this->_text = preg_replace_callback(
                '/src="([^"]*)"/',
                array($this, 'embed_images'),
                $this->_text);
        }
        return true;
    }

    function getTemplate($uri = '', $get_params = array(), $ei = false)
    {
        if ($uri == '') return false;

        if ($get_params !== array()) {
            $uri .= '?' . http_build_query($get_params);
        }

        if (!$template = file_get_contents($uri)) {
            return false;
        }

        $this->_text = $this->minify_html($template);

        if ($ei) {
            $this->_text = preg_replace_callback(
                '/src="([^"]*)"/',
                array($this, 'embed_images'),
                $this->_text);
        }
        return true;
    }

    function send()
    {

        //$this->headers[] = 'Date: ' . date('r');

        if (count($this->_bcc))
            $this->headers[] = 'BCC: ' . (implode(', ', $this->_bcc));

        $this->headers[] = 'To: ' . ($this->_to_name ? '=?' . $this->charset . '?B?' . base64_encode($this->_to_name) . '?= ' : '') . '<' . $this->_to . '>';

        if($this->_reply_to)
            $this->headers[] = 'Reply-To: ' . $this->_reply_to;

        if (count($this->_cc))
            $this->headers[] = 'Cc: ' . (implode(', ', $this->_cc));

        $this->headers[] = 'From: ' . ($this->_from_name ? '=?' . $this->charset . '?B?' . base64_encode($this->_from_name) . '?= ' : '') . '<' . $this->_from . '>';
        $this->headers[] = 'Subject: ' . ($this->_subject !== '' ? '=?' . $this->charset . '?B?' . base64_encode($this->_subject) . '?=' : '');
        $this->headers[] = 'X-Mailer: ' . $this->mailer;
        $this->headers[] = $this->mime_version;

        if (count($this->attache) > 0) {
            $this->headers[] = "Content-Type: multipart/mixed;\r\n boundary=\"{$this->mixed_boundary}\"\r\n";
            $this->headers[] = "--{$this->mixed_boundary}";
        }

        if (count($this->embeded) > 0) {
            $this->headers[] = "Content-Type: multipart/related;\r\n boundary=\"{$this->related_boundary}\"\r\n";
            $this->headers[] = "--{$this->related_boundary}";
        }

        $this->headers[] = "Content-Type: text/html; charset={$this->charset}";
        $this->headers[] = "Content-Transfer-Encoding: base64" . "\r\n";
        $this->headers[] = chunk_split(base64_encode($this->_text), 76) . "\r\n";

        if (count($this->embeded) > 0) {
            $this->headers[] = implode('', $this->embeded);
            $this->headers[] = "--{$this->related_boundary}--\r\n";
        }

        if (count($this->attache) > 0) {
            $this->headers[] = implode('', $this->attache);
            $this->headers[] = "--{$this->mixed_boundary}--\r\n\r\n";
        }

        $this->headers[] = "\r\n.";
        $this->data = implode("\r\n", $this->headers);

        $this->addresses[] = $this->_to;
        $this->addresses = array_unique(
            array_merge($this->addresses, $this->_bcc, $this->_cc)
        );

        if($this->config['auth'] === false){
            return mail(NULL, NULL, NULL, $this->data);
        }

        return $this->smtp_send_mail();
    }


    function mime_type($filename)
    {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        $ext = explode('.', $filename);
        $ext = array_pop($ext);
        $ext = strtolower($ext);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }

        return 'application/octet-stream';
    }

    function attachment($file, $octet_stream = true)
    {
        if (!$file_data = file_get_contents($file))
            return false;

        $basename = basename($file);
        $attach = "--{$this->mixed_boundary}\r\n";
        $attach .= "Content-Type: " . ($octet_stream ? 'application/octet-stream' : ($this->mime_type($file))) . "; name=\"=?" . $this->charset . "?B?" . base64_encode($basename) . "?=\"\r\n";
        $attach .= "Content-Transfer-Encoding: base64\r\n";
        $attach .= "Content-Disposition: attachment; name=\"=?" . $this->charset . "?B?" . base64_encode($basename) . "?=\"\r\n\r\n";
        $attach .= chunk_split(base64_encode($file_data), 76);
        $this->attache[] = $attach;

        return true;
    }

    function attachmentContent($filename, $content, $mime_type = 'application/octet-stream')
    {
        $attach = "--{$this->mixed_boundary}\r\n";
        $attach .= "Content-Type: " . ($mime_type) . "; name=\"=?" . $this->charset . "?B?" . base64_encode($filename) . "?=\"\r\n";
        $attach .= "Content-Transfer-Encoding: base64\r\n";
        $attach .= "Content-Disposition: attachment; name=\"=?" . $this->charset . "?B?" . base64_encode($filename) . "?=\"\r\n\r\n";
        $attach .= chunk_split(base64_encode($content), 76);
        $attach .= "\r\n";
        $this->attache[] = $attach;
        return true;
    }


    function embed($file)
    {
        if (!$file_data = file_get_contents($file))
            return false;
        $content_id = md5(rand()) . strstr($this->_from, '@');
        $mime_type = $this->mime_type($file);
        list($type, $ext) = explode('/', $mime_type);
        $name = (md5(rand())) . ".{$ext}";

        $embed = "--{$this->related_boundary}\r\n";
        $embed .= "Content-Type: " . $mime_type . ";\r\n name=\"{$name}\"\r\n";
        $embed .= "Content-Transfer-Encoding: base64\r\n";
        $embed .= "Content-ID: <{$content_id}>\r\n";
        $embed .= "Content-Disposition: inline;\r\n filename=\"{$name}\"\r\n\r\n";
        $embed .= chunk_split(base64_encode($file_data), 76) . "\r\n";
        $this->embeded[] = $embed;
        return "cid:{$content_id}";
    }

    private function socket_open(){
        if(!$this->socket = @fsockopen(($this->config['ssl'] ? 'ssl://' : '') . $this->config['host'], $this->config['port'])){
            return false;
        }
        return true;
    }

    private function read()
    {
        if(!$this->socket) return false;
        $res = fgets($this->socket, 256);
        return substr($res, 0, 3);
    }

    private function write($str)
    {
        if(!$this->socket) return false;
        return fwrite($this->socket, $str."\r\n");
    }

    function command($command)
    {
        if(!$this->socket) return false;
        $this->write($command);
        return $this->read();
    }

    function close()
    {
        if(!$this->socket) return false;
        return fclose($this->socket);
    }


    private function smtp_send_mail()
    {
        if(!$this->socket_open()){
            $this->error = 'Failed to even make a connection';
            return false;
        }

        if($this->read() != '220'){
            $this->error = 'Failed to connect';
            return false;
        }

        if($this->command("HELO " . $this->http_host) != '250'){
            $this->error = 'Failed to Introduce';
            return false;
        }

        if ($this->config['auth']) {

            if($this->command("AUTH LOGIN") != '334'){
                $this->error = 'Failed to Initiate Authentication';
                return false;
            }

            if($this->command(base64_encode($this->config['user'])) != '334'){
                $this->error = 'Failed to Provide Username for Authentication';
                return false;
            }

            if($this->command(base64_encode($this->config['pass'])) != '235'){
                $this->error = 'Failed to Authenticate password';
                return false;
            }
        }

        if($this->command("MAIL FROM: <" . $this->config['user'] . ">") != '250'){
            $this->error = 'MAIL FROM failed';
            return false;
        }

        foreach ($this->addresses as $k => $v) {
            if($this->command("RCPT TO: <" . $v . ">") != '250'){
                $this->error = 'MAIL FROM failed';
                return false;
            }
        }

        if($this->command("DATA") != '354'){
            $this->error = 'DATA failed';
            return false;
        }

        if($this->command($this->data) != '250'){
            $this->error = 'Message Body Failed';
            return false;
        }

        if($this->command("QUIT") != '221'){
            $this->error = 'QUIT failed';
            return false;
        }

        if (!$this->close()) {
            $this->error = 'fsock not closed';
        }


        return true;
    }
}
