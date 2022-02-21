<?php


namespace app\common\model;


class JiemaProjectModel extends BaseModel
{
    public function getTypeAttr($value){
        $type = [1 =>'接收', 2 =>'发送', 3 => '充值', 4 => '赠送', 5 => '扣款'];
        return $type[$value];
    }

    //获取某个字段的值
    public function getFieldValue($pid, $field){
        $result = self::where('pid', $pid)
            ->value($field);
        return $result;
    }

    public function getInfo($pid){
        $result = self::where('pid', $pid)->find();
        return $result;
    }

    public function createProject($data){
        $result = self::create($data);
        return $result;
    }

    public function getAllProject($page, $limit){
        $result = self::order('id', 'desc')
            ->where('price', '<>', 0)
            ->page($page, $limit)
            ->select();
        return $result;
    }

    //前台用户获取所属项目 user='' or user = self
    public function getUserProject($user){
        $result = self::where("user = :user or user = ''", ['user' => $user])
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select();
        return $result;
    }
}