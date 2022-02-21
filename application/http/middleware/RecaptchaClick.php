<?php

namespace app\http\middleware;

use app\common\controller\RedisController;
use think\Controller;
use bt\Bt;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;
use Ip2Region;

class RecaptchaClick extends Controller
{
    public function handle($request, \Closure $next)
    {
        $ip = real_ip();
        /*$ip_addr = getIpRegion((new Ip2Region())->btreeSearch($ip)['region']);
        if (strstr($ip_addr, '上海')){
            Log::record($ip_addr, 'notice');
        }*/
        $redis = new RedisController();
        $impose_number = $redis->redisCheck('impose_' . $ip);
        $robot_number = $redis->redisCheck('robot_' . $ip);
        //$blicklist = $redis->redisCheck('blacklist_' . $ip);

        if ($impose_number || $robot_number){
            $redis->redisNumber('impose_' . $ip);
            if ($impose_number > 50){
                $redis->redisNumber('robot_' . $ip, 21600);
                $firewall = $redis->redisCheck('firewall');
                if ($robot_number > 60 && !$firewall){
                    //由于调用接口速度慢，同一时间只允许一个请求调用，否则会重复拉黑。
                    $redis->redisNumber('firewall', 30);
                    $redis->del('robot_' . $ip);
                    $bt = (new Bt())->fireWall($ip, 'BT防火墙黑名单,robot大于60');
                    if($bt){
                        $redis->deleteString('firewall');
                    }
                }
            }
            //Log::record($ip . ":impose_number:" . $impose_number . ":robot_number:" . $robot_number, 'notice');
            return $this->error(Lang::get('api_recaptcha_request_speed_fast'));
        }

        //过滤蜘蛛
        if (!checkSpider($ip)){
            //由于checkspider有调用单例redis,这里必须重新new
            $redis = (new RedisController());
            /**
             * 第一次访问允许通过，但是需要recaptcha AJAX提交返回一个分数记录，第二次访问时候检查score是否存在并且大于0.1，否则不允许
             * 该hash,ttl=3600,一个小时内审核该ip一次
             * hash存储，ip:click:127.0.0.1
             * total: 1 是否是第一次访问
             * score: 0.9 recaptcha返回的分数，
             */
            $key_ip_click = 'ip:click:' . $ip;
            $click_ip = $redis->hMget($key_ip_click, ['message_click', 'score']);
            if (!$click_ip['message_click']){
                //第一次访问，start = 1
                $redis->hSetNxTtl($key_ip_click, 'message_click', 1);
            }else{
                //第二次访问，start自增
                $redis->hSet($key_ip_click, 'message_click', $click_ip['message_click'] + 1);
                //判断score是否存在
                //可以请求十次，十次之后，需要recaptcha验证一下分数，静默请求？
                if ($click_ip['message_click'] > 10){
                    if (!$click_ip['score']){
                        if (Request::method() == 'POST'){
                            return show('<span>Jump to the /spi page and try again！<a href="/spi">Click Me</a></span>', '/spi', 4003);
                        }
                        $this->redirect('/spi');
                    }elseif($click_ip['score'] < 0.2){
                        $this->redirect('/spi');
                    }
                }
            }
            
            //1s钟超过2次访问，或者十分钟内超过60次。直接异常2个小时
            $ip_1s = $redis->redisNumber('ip_1s_' . $ip, 1);
            $ip_600s = $redis->redisNumber('ip_600s_' . $ip, 600);
            $ip_3600s = $redis->redisNumber('ip_3600s_' . $ip, 3600);

            if ($ip_1s > 2 || $ip_600s > 100){

                if ($ip_3600s > 150){
                    $redis->redisNumber('impose_' . $ip, 1800);
                    $redis->redisNumber('robot_' . $ip, 21600);
                    //(new Bt())->fireWall($ip, 'BT防火墙黑名单,3600秒超过200');
                }
                //return $this->error(Lang::get('api_recaptcha_request_speed_fast'),'','', 5);
                if (Request::method() == 'POST'){
                    return show('<span>Jump to the /spi page and try again！<a href="/spi">Click Me</a></span>', '/spi', 4003);
                }else{
                    $this->redirect('/spi');
                }
            }

        }
        return $next($request);
    }
}
