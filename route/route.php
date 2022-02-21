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
if ($sub_domain == env('SETTING.SUBDOMAIN', 'best20161108')){
	// blog子域名绑定到blog模块
	Route::domain($sub_domain, 'simple20161108');
	Route::get('verify', 'simple20161108/Login/verify'); //验证码
	return;
}

Route::get('simple20161108', 'index/NoFound/index'); //仅允许域名访问




