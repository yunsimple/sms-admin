<?php

namespace app\simple20161108\controller;


class SubmitBaiduController
{
    public function curlBaidu($url){
        //$domain = Request::domain();
        $urls = array($url);
        $api = [
            'http://data.zz.baidu.com/',
            'http://data.zhanzhang.sm.cn/'
        ];
        $result = [];
        for ($i = 0; $i < count($api); $i++){
            $ch = curl_init();
            $options =  array(
                CURLOPT_URL => $api[$i],
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => implode("\n", $urls),
                CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result[$i] = curl_exec($ch);
        }
        return json_encode($result);
    }
}