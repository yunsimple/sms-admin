<?php

namespace app\api\validate;


use think\Validate;

class ApiValidate extends Validate
{
    protected $rule = [
      'phone_num|号码' => 'require|max:20|min:7|number'
    ];
}