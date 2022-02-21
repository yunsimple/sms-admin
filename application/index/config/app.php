<?php

return [
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => Env::get('app_path') . 'index/tpl/jump.html',
    'dispatch_error_tmpl'    => Env::get('app_path') . 'index/tpl/jump.html',
    'exception_tmpl'         => Env::get('app_path') . 'index/tpl/exception.html',
    'default_lang'           => 'zh-cn_old',
];