<?php

namespace app\common\model;

use think\facade\Session;

class UserSmsModel extends BaseModel
{
    //登陆查询用户登陆信息
    public function getUserInfo($user){
        $result = self::where('username', $user)->find();
        return $result;
    }

    //新增用户
    public function insertUser($data){
        $result = self::create($data);
        return $result;
    }

    //获取某个字段的值
    public function getFieldValue($user, $field){
        $result = self::where('name', $user)
            ->value($field);
        return $result;
    }

    //余额增加减少
    public function changeMoney($user, $money){
        if ($money > 0){
            $result = self::where('name', $user)
                ->setInc('money', $money);
        }else{
            $result = self::where('name', $user)
                ->setDec('money', abs($money));
        }
        return $result;
    }

    //后台显示所有帐户
    public function getAllUser($page, $limit){
        $result = self::where('type', '<>', 1)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }
}