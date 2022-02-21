<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

$sub_domain = get_subdomain();
$domain = get_domain();

if ($sub_domain == 'best20161108'){
	// blog子域名绑定到blog模块
	Route::domain('best20161108', 'simple20161108');
	Route::get('verify', 'simple20161108/Login/verify'); //验证码
	return;
}

Route::get('simple20161108', 'index/NoFound/index'); //仅允许域名访问

if ($sub_domain == 'appapi'){
    Route::post('phone', 'appapi/Phone/getPhone');
    Route::post('country', 'appapi/Country/getCountry');
    Route::post('message', 'appapi/Message/getMessage');
    Route::post('blog', 'appapi/Country/getBlog');
    Route::post('random', 'appapi/Phone/getPhoneRandom');
    Route::post('report', 'appapi/Phone/report');
    Route::post('email_get', 'appapi/Email/emailGet');
    Route::post('email_apply', 'appapi/Email/emailApply');
    Route::post('email_user_delete', 'appapi/Email/emailUserDelete');
    Route::get('email_delete', 'appapi/Email/emailDelete');
    Route::post('email_transpond', 'appapi/Email/setTranspondEmail');
    Route::post('email_site', 'appapi/Email/getEmailSite');
    Route::post('login', 'appapi/Token/getToken');
    Route::post('login_out', 'appapi/Token/loginOut');
    Route::post('access', 'appapi/Token/getAccessByRefresh');
    Route::post('register', 'appapi/User/register');
    Route::post('my', 'appapi/User/getMy');
    Route::post('update', 'appapi/Update/getUpdate');
    Route::post('getinfo', 'appapi/Update/getInfo');
    Route::post('countrys', 'appapi/Phone/getPhones');
    Route::post('params', 'appapi/Params/getParams');
    return;
}

$countrys = '(?i)Upcoming|China|GAT|GW|Foreign|UK|USA|Myanmar|Estonia|Philippines|HongKong|Macao|Indonesia|Australia|Canada|Malaysia|Japan|Korea|Russia|Thailand|India|Mexico|Vietnam|Nigeria|Taiwan|Colombia|Bangladesh|Pakistan|Iran|Egypt|Argentina|Ukraine|Venezuela|Turkey|SouthAfrica|Spain|Serbia|Portugal|Poland|Netherlands|Italy|Germany|France|CzechRepublic|Croatia|Brazil|Sweden|Netherlands';
Route::group('', function () use ($countrys){
    // 动态注册域名的路由规则
    //country跳转
    Route::get('/', 'best/Phone/index');
    Route::get('receive-sms-blog/page:page', 'best/Article/index');
    Route::get('receive-sms-blog/:id', 'best/Article/detail');
    Route::get('receive-sms-blog', 'best/Article/index');


    Route::get('temporary-email', 'best/Mail/index');

    Route::get('receive-sms-from-:country/:phone_num/:page', 'best/Message/index')->pattern(['country'=>$countrys, 'phone_num'=>'\d{5,}']);
    Route::get('receive-sms-from-:country/:phone_num', 'best/Message/index')->pattern(['country'=>$countrys, 'phone_num'=>'[a-z0-9]{7,16}']);
    //首页Page
    Route::get('phone-number/:page', 'best/Phone/index')->pattern(['page'=>'\d{0,3}']);
    Route::get(':country-phone-number/:page', 'best/Phone/index')->pattern(['country'=>$countrys, 'page'=>'\d{0,3}']);
    Route::get(':country-phone-number', 'best/Phone/index')->pattern(['country'=>$countrys]);
    Route::get('country/:page', 'best/Country/index');
    Route::get('country', 'best/Country/index');


    Route::get('receive-sms-from-:project', 'best/Project/index');
    Route::get('receive-sms-from', 'best/Project/show');
    Route::get('sitemap', 'best/Phone/sitemap');
    Route::post('random', 'best/Message/random');
    Route::get('wp-password', 'best/Wangpan/password');
    Route::get('generate-image', 'best/simple20161108/ServiceQueue/grentimage');

    //Route::post('api/getsms', 'api/Api/getSMS');
    //Route::post('api/getsmsnumber', 'api/Api/postSmsNumber');
    //Route::post('api/search_number', 'api/Api/postOneNumber');
    Route::post('api/get_region_num', 'api/Api/getRegionNum');
    Route::post('api/email_get', 'api/Mail/emailGet');
    Route::post('api/email_apply', 'api/Mail/emailApply');
    Route::post('api/email_user_delete', 'api/Mail/emailUserDelete');
    Route::post('api/email_delete', 'api/Mail/emailDelete');
    Route::post('api/email_transpond', 'api/Mail/setTranspondEmail');
    Route::post('insert_local_sms85445665', 'admin20161108/MsgQueue/insertLocalSMS');

    Route::post('api/mailbox', 'best/Subscription/mailbox');
    Route::post('api/sendTestmail', 'best/Subscription/sendTestmail'); //取消订阅页面
    Route::post('api/unsubscribe', 'best/Subscription/unsubscribe'); //取消订阅api
    Route::get('unsubscribe', 'best/Subscription/unsubscribePage'); //取消订阅页面

    Route::post('api/feedback', 'best/Feedback/create');
    Route::post('api/wp_password', 'best/Wangpan/getPassword');
    Route::post('recaptcha', 'best/ReCaptcha/index');
    Route::post('hcaptcha', 'best/ReCaptcha/hcaptcha');
    Route::post('report', 'best/Message/report');
    Route::get('spi', 'best/Check/index'); //蜘蛛重命名
    Route::get('verify', 'simple20161108/Login/verify'); //验证码

    Route::post('heartbeat', 'index/Index/heartBeat');

})->crossDomainRule();


/*$countrys = '(?i)Upcoming|China|GAT|GW|Foreign|UK|USA|Myanmar|Estonia|Philippines|HongKong|Macao|Indonesia|Australia|Canada|Malaysia|Japan|Korea|Russia|Thailand|India|Mexico|Vietnam|Nigeria|Taiwan|Colombia|Bangladesh|Pakistan|Iran|Egypt|Argentina|Ukraine|Venezuela|Turkey|SouthAfrica|Spain|Serbia|Portugal|Poland|Netherlands|Italy|Germany|France|CzechRepublic|Croatia|Brazil|Sweden|Netherlands';
    Route::group('', function () use ($countrys){
        //return;
        // 动态注册域名的路由规则
        //country跳转
        Route::get('/', 'mys/Phone/index');
        Route::get('index', 'mys/Phone/index');
        Route::get('receive-sms-online/', 'mys/Phone/index');
        Route::get('receive-sms-online/article/:id', 'mys/Article/detail');
        Route::get('receive-sms-online/article', 'mys/Article/index');
        Route::get('receive-sms-online/blog/:id', 'mys/Article/detail')->cache(['__URL__', 3600]);
        Route::get('receive-sms-online/blog', 'mys/Article/index');
        
        Route::get('Mail', 'mys/Mail/index');
        Route::get('receive-sms-online/country', 'mys/Country/index');
        Route::get('receive-sms-online/:country-phone-number-:phone_num/:page', 'mys/Message/index')->pattern(['country'=>$countrys, 'phone_num'=>'\d{5,}']);
        Route::get('receive-sms-online/:country-phone-number-:phone_num', 'mys/Message/index')->pattern(['country'=>$countrys, 'phone_num'=>'[a-z0-9]{7,16}']);
        Route::get('receive-sms-online/:country-Phone-number/page/:page', 'mys/Phone/index')->pattern(['country'=>$countrys, 'page'=>'\d{0,3}']);
        Route::get('receive-sms-online/:country-phone-number', 'mys/Phone/index')->pattern(['country'=>$countrys]);
        Route::get('receive-sms-online/phone-number/page/:page', 'mys/Phone/index')->pattern(['page'=>'\d{0,3}']);
        Route::get('receive-sms-from/:project', 'mys/Project/index');
        Route::get('sitemap', 'mys/Phone/sitemap');
        Route::post('random', 'mys/Message/random');
        Route::get('wp-password', 'mys/Wangpan/password');
        Route::get('generate-image', 'mys/simple20161108/ServiceQueue/grentimage');
        
        //Route::post('api/getsms', 'api/Api/getSMS');
        //Route::post('api/getsmsnumber', 'api/Api/postSmsNumber');
        //Route::post('api/search_number', 'api/Api/postOneNumber');
        Route::post('api/get_region_num', 'api/Api/getRegionNum');
        Route::post('api/email_get', 'api/Mail/emailGet');
        Route::post('api/email_apply', 'api/Mail/emailApply');
        Route::post('api/email_user_delete', 'api/Mail/emailUserDelete');
        Route::post('api/email_delete', 'api/Mail/emailDelete');
        Route::post('api/email_transpond', 'api/Mail/setTranspondEmail');
        Route::post('insert_local_sms85445665', 'admin20161108/MsgQueue/insertLocalSMS');
        
        Route::post('api/mailbox', 'mys/Subscription/mailbox');
        Route::post('api/sendTestmail', 'mys/Subscription/sendTestmail'); //取消订阅页面
        Route::post('api/unsubscribe', 'mys/Subscription/unsubscribe'); //取消订阅api
        Route::get('unsubscribe', 'mys/Subscription/unsubscribePage'); //取消订阅页面
        
        Route::post('api/feedback', 'mys/Feedback/create');
        Route::post('api/wp_password', 'api/Wangpan/getPassword');
        Route::post('recaptcha', 'index/ReCaptcha/index');
        Route::post('hcaptcha', 'index/ReCaptcha/hcaptcha');
        Route::post('report', 'mys/Message/report');
        Route::get('check', 'index/Check/index');
        Route::get('spi', 'index/Check/index'); //蜘蛛重命名
        Route::get('verify', 'admin20161108/Login/verify'); //验证码
        
        Route::post('heartbeat', 'index/Index/heartBeat');
        
    })->crossDomainRule();*/


