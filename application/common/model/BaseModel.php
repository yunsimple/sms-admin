<?php

namespace app\common\model;

use think\Model;

class BaseModel extends Model
{
    //自增字段
    public function setIncNum($find_field, $value, $inc_title){
        self::where($find_field, '=', $value)
            ->setInc($inc_title);
    }
}