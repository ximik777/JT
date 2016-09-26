<?php

namespace JT\Yandex;

class PDD
{
    private $error;
    private $errno;

    private $config = array(
        'url' => 'https://pddimp.yandex.ru/api2',
        'domain' => '',
        'token' => ''
    );

    public function getError()
    {
        return $this->error;
    }

    public function getErrno()
    {
        return $this->errno;
    }

    function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    function addSubdomain($subdomain, $ip)
    {
        return $this->request('/admin/dns/add', [
            'type' => 'A',
            'content' => $ip,
            'subdomain' => $subdomain
        ], true);
    }

    function getRecords()
    {
        return $this->request('/admin/dns/list');
    }

    function deleteRecord($record_id)
    {
        return $this->request('/admin/dns/del', [
            'record_id' => $record_id
        ], true);
    }

    function deleteSubdomain($subdomain)
    {
        if (!$subdomain) {
            return false;
        }

        if ($records = $this->getRecords()) {

            $record_id = 0;

            foreach ($records['records'] as $k => $v) {
                if ($v['subdomain'] == $subdomain) {
                    $record_id = $v['record_id'];
                    break;
                }
            }

            if ($record_id == 0) {
                return true;
            }

            return $this->deleteRecord($record_id);
        }

        return false;
    }

    private function request($method, $data = array(), $post = false)
    {
        $this->errno = 0;
        $this->error = '';

        $ch = curl_init();

        $url = $this->config['url'] . $method;

        $data['domain'] = $this->config['domain'];

        $data = http_build_query($data);

        if (!$post) {
            $url .= '?' . $data;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("PddToken: " . $this->config['token']));

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);

        $this->errno = curl_errno($ch);
        $this->error = curl_error($ch);

        curl_close($ch);

        if ($this->errno > 0) {
            return false;
        }

        if (!$json = json_decode($response, true)) {
            return false;
        }

        if($json['success'] == 'error'){
            $this->error = $json['error'];
            $this->errno = -1;
            return false;
        }

        return $json;
    }


}