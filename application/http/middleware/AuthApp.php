<?php


namespace app\http\middleware;


use app\appapi\controller\RedisController;
use Symfony\Component\DependencyInjection\Tests\Compiler\DummyExtension;
use think\Controller;

class AuthApp extends Controller
{
    public function handle($request, \Closure $next)
    {
        /**
         * 1.检查access_token是否存在
         * 2.检查refresh还有多久失效，如果快失效了，延期
         * 3.每天打开app应用检查refresh过期时间
         */
        $salt = 'ca-app-pub-3940256099942544~334759533';
        $headers = getallheaders();
        if (!array_key_exists('Access-Token', $headers)) {
            return show('鉴权失败', '', 4003, '', 403);
        }
        if (!array_key_exists('Authorization', $headers)) {
            return show('鉴权失败', '', 4003, '', 403);
        }

        $access_token = $headers['Access-Token'];
        $access_token_key = 'app:accessToken:' . $access_token;

        $redis = new RedisController();
        if (!$redis->exists($access_token_key)) {
            return show('鉴权失败', '', 4003, '', 403);
        }

        //比对Authorization
        //encode(md5(access_token + random_str + salt) + 'a' + random_str)
        $authorization = $headers['Authorization'];
        $authorization = base64_decode($authorization);
        //防止重放攻击
        if (!$redis->sAddEx('app:auth:' . $authorization)){
            return show('鉴权失败', '', 4003, '', 403);
        }

        //$salt = '兄台爬慢点可好';
        $random_str = substr($authorization, -9);
        $random_str_fake = 'a' . $random_str;
        if (md5($access_token . $random_str . $salt) . $random_str_fake !== $authorization) {
            return show('鉴权失败', '', 4003, '', 403);
        }

        //验证通过 对access和refres进行请求统计，以便后面做限流
        $redis->hIncrBy($access_token_key, 'requestNumber', 1);
        $refresh_token = $redis->hGet($access_token_key, 'refreshToken');
        $refresh_token_key = 'app:refreshToken:' . $refresh_token;
        $redis->hIncrBy($refresh_token_key, 'requestNumber', 1);

        //把Authorization写入redis无序列表，做重放攻击限制
        $redis->sAddEx('app:auth:' . $authorization);
        //传出一个token的有效时间，其他控制器使用
        $request->header = ['Expires' => $redis->ttl($access_token_key)];

        return $next($request);
    }
}