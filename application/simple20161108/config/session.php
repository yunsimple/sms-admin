<?php
return [
    'id'             => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // SESSION 前缀
    'prefix'         => 'think',
    // 驱动方式 支持redis memcache memcached
    'type'           => 'redis',
    // redis主机
    'host'       => '127.0.0.1',
    // redis端口
    'port'       => 6379,
    // 密码
    //'password'   => '1Yt8cAFjm',
    // 是否自动开启 SESSION
    'auto_start'     => true,
    //有效期
    'expire'         => 86400*7
];
