<?php

namespace app\common\controller;

use think\Controller;
use qcloudsms\SmsSingleSender;

class SendSmsController extends Controller
{
    public function sendSms($phone_num)
    {
        //play QQ号

        //play2 微信

        $appid = '';
        $appkey = '';
        $templateId = '';

        $smsSign = '';
        $phoneNumbers = [''];

    // 指定模板ID单发短信
        try {
            $ssender = new SmsSingleSender($appid, $appkey);
            $params = [$phone_num];
            $result = $ssender->sendWithParam("86", $phoneNumbers[0], $templateId,
                $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            //echo $result;
        } catch (\Exception $e) {
            //echo var_dump($e);
        }
    }
}