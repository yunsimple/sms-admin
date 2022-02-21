<?php

namespace app\common\model;


use think\Model;

class HistoryModel extends Model
{
    //保存
    public function createValue($data){
        $result = self::create($data);
        return $result->id;
    }

    //查询最近一个月的采集情况
    public function searchTime($day){
        $result = self::order('id', 'desc')
                ->limit($day)
                ->select();
        return $result;
    }
}