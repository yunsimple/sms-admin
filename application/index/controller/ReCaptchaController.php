<?php

namespace app\index\controller;

use app\common\controller\RedisController;
use think\Controller;
use think\facade\Log;
use app\common\controller\ReCaptchaController as Recaptcha;
use think\facade\Request;

class ReCaptchaController extends Controller
{
    /**
     * google防水墙
     * @return bool|mixed|string
     */
    public function index(){
        $ip = real_ip();
        $page = input('param.page');
        $token = input('param.token');
        $key = 'ip:click:' . $ip;
        $redis = new RedisController();

        if ($page == 'check'){
            /*if ($ip == '154.17.23.155'){
                return show('fail', '', 4000);
            }*/
            //系统第二次检查check页面
            $result = (new Recaptcha())->resolveReCaptcha($token);
            $result = json_decode($result, true);
            if ($result['success']){
                $redis->hSet($key, 'score', $result['score']);
                $redis->incr('recaptcha_' . Request::rootDomain());
                return show('success', $result['score']);
            }else{
                return show('fail', '', 4000);
            }
        }else{
            //判断是否第一次访问，如果是则验证recaptcha
            $ip_detail = $redis->hMget($key, ['message_click', 'score']);

            if ($ip_detail['message_click'] && $ip_detail['message_click'] < 3 && !$ip_detail['score']){
                //第一次访问通过，仅需要ajax提交一次recaptcha验证
                $result = (new Recaptcha())->resolveReCaptcha($token);
                $result = json_decode($result, true);
                if ($result['success']){
                    $redis->hSet($key, 'score', $result['score']);
                    $redis->incr('recaptcha_' . Request::rootDomain());
                    return $result['score'];
                }else{
                    return 0;
                }
            }else{
                return 0.6;
            }
        }
    }
    
    public function hcaptcha(){
        $hid = Request::param('response');
        if (!$hid){
            return show('System verification failed', '', 4000);
        }
        //验证数据
        $ip = real_ip();
        $data = [
            'secret' => '0x65Bf6f92F4BE9A0A544ebC34d3CA96dCe2748647',
            'response' => $hid,
            'remoteip' => $ip
        ];
        $result = curl_post('https://hcaptcha.com/siteverify', $data);
        $result = json_decode($result, true);
        if ($result['success']){
            //Log::record('hcaptcha验证通过' . $ip, 'notice');
            $redis = new RedisController();
            //查看该ip是否存在 hvals ip:click:$ip
            $result = $redis->hSetTtl("ip:click:" . $ip, 'score', 0.5);
            return show('success');
        }else{
            Log::record('hcaptcha验证失败' . $ip, 'notice');
            return show('System verification failed', '', 4000);
        }
    }
}