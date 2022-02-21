<?php
/**
 * 自定义配置文件
 * Created by PhpStorm.
 * Date: 2019-06-22 0022
 * Time: 12:50
 */
return [
    'geetest' => [
        'captcha_id' => '',
        'private_key' => ''
    ],
    '3001'=> '未提交Access-token',
    '4003'=> 'Access-token鉴权失败',
    '4004'=> 'refresh-token不存在',
    'web' => [

    ],
    'app' => [
        'token_expires' => 30*60 //设置app token失效时间
    ],
    'common' => [
        'trap_minutes' => 39, //每个小时的多少分钟，验证码图片显示网址
    ]

];