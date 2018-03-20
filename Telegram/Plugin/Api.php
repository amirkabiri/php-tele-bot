<?php

namespace Telegram\Plugin;

class Api{

    public $token = null;

    public function __construct($token)
    {
        $this->token = $token;
    }
    public function request($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            error_log(curl_error($ch));
            return null;
        } else {
            return $res;
        }
    }
    function api($method, $data = []){
        return json_decode($this->request('https://api.telegram.org/bot'.$this->token.'/'.$method, $data));
    }
    function __call($name, $arguments)
    {
        return $this->api($name, $arguments[0]);
    }

}