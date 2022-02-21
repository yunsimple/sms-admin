<?php

namespace app\http\middleware;

use app\common\controller\RedisController;
use think\Controller;
use bt\Bt;
use think\facade\Log;

class Click extends Controller
{
    public function handle($request, \Closure $next)
    {
        
        $ip = real_ip();
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
            return $this->error('访问频率过高，稍候再试.');
        }

        //过滤蜘蛛
        if (!checkSpider($ip)){
            //由于checkspider有调用单例redis,这里必须重新new
            $redis = (new RedisController());
            //1s钟超过2次访问，或者十分钟内超过60次。直接异常2个小时
            $ip_1s = $redis->redisNumber('ip_1s_' . $ip, 1);
            $ip_600s = $redis->redisNumber('ip_600s_' . $ip, 600);
            $ip_3600s = $redis->redisNumber('ip_3600s_' . $ip, 3600);

            if ($ip_1s > 3 || $ip_600s > 100){
                
                $redis->redisNumber('impose_' . $ip, 1800);
                if ($ip_3600s > 200){
                    $redis->redisNumber('robot_' . $ip, 21600);
                    //(new Bt())->fireWall($ip, 'BT防火墙黑名单,3600秒超过200');
                }
                //Log::record($ip . ":impose_number:" . $impose_number . ":robot_number:" . $robot_number, 'notice');
                return $this->error('访问频率过高，稍候再试');
            }

        }
        return $next($request);
    }
}
