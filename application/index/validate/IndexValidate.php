<?php

namespace app\index\validate;


use think\Validate;

class IndexValidate extends Validate
{
    protected $rule = [
        'phone_num|号码' => 'max:20|min:7|number'
    ];
}