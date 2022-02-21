<?php

namespace app\simple20161108\validate;


use think\Validate;

class PhoneValidate extends Validate
{
    protected $rule = [
        'phone_num|号码' => 'require|max:12|min:7|number',
        'country_id|国家' => 'require|max:10',
        'warehouse_id|仓库' => 'require|number|max:3',
        'show|是否显示' => 'number|max:1',
        'online|在线状态' => 'number|max:1',
    ];
}