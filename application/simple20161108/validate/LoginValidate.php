<?php

namespace app\simple20161108\validate;


use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        'username|用户名' => 'require|max:32|alphaNum',
        'password|密码' => 'require|max:32',
        'captcha|验证码'=>'require|captcha|length:4'
        
    ];
}