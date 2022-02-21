<?php

namespace app\index\controller;

use app\common\controller\RedisController;
use bt\Bt;
use think\Controller;
use think\facade\Request;

class BaseController extends Controller
{
    //流量限制,每秒访问超过4次,直接封IP
    public function initialize()
    {
    	//$redis = new RedisController();
        //$ip = $_SERVER["REMOTE_ADDR"];
        //$ip = real_ip();
/*    	$user_agent = $_SERVER['HTTP_USER_AGENT'];
        $agent = [
            'Apache-HttpClient/UNAVAILABLEjava1.4',
            'Go-http-client/1.1',
        ];
        if (in_array($user_agent, $agent)){
            //return $this->error('访问异常');
            //(new Bt())->fireWall($ip, 'user_agent异常');
            $api_redis->redisNumber('impose_' . $ip, 21600);
        }*/
        //$redis->delPrefixRedis('ip_');
/*        if ($redis->redisCheck('impose_' . $ip)){
        	return $this->error('访问异常');
        }
        //过滤蜘蛛
		if (!is_crawler()){
        	$ip_number = $redis->redisNumber('ip_' . $ip, 60);
			
	        if ($ip_number > 20000){
	            //(new Bt())->fireWall($ip, 'base拦截,每秒超过5次');
	            //$redis->redisNumber('test_' . $ip , 100);
	            $redis->redisNumber('impose_' . $ip, 7200);
	            //return $this->error('访问异常');
	        }
        
		}*/
        
    }
}