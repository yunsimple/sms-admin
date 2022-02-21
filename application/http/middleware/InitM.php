<?php


namespace app\http\middleware;


use think\Controller;
use think\exception\HttpException;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;

class InitM extends Controller
{
    public function handle($request, \Closure $next){
        //屏蔽部分ip
        $sub = get_subdomain();
        if ($sub == 'www'){
            Lang::load('../application/lang/en.php');
        }else{
            Lang::load('../application/lang/'.$sub.'.php');
        }
        return $next($request);
    }
}