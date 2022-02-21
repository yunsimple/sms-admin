<?php

namespace app\common\controller;


use think\Controller;
use think\facade\Log;
use think\facade\Request;

class ReCaptchaController extends Controller
{
    protected $url = 'https://www.recaptcha.net/recaptcha/api/siteverify';
    protected $key = '6Lf71VQcAAAAAGbjRxX9eWlJqRx--OXjnrTbySN5';
    
    
    public function resolveReCaptcha($token){
        $param = [
            'secret' => $this->key,
            'response' => $token
        ];
        $result = curl_post($this->url,$param);
        return $result;
    }
}