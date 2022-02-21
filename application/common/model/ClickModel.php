<?php

namespace app\common\model;


class ClickModel extends BaseModel
{
    //关联模型
    public function phone(){
        return $this->belongsTo('PhoneModel', 'phone_id', 'id');
    }

    public function click($data){
        $result = self::create($data);
        return $result->id;
    }

    //根据日期查询点击情况
    public function searchClickPhone($time){
        $result = self::alias('c')
            ->where('c.phone_id', '>', 0)
            ->group('c.phone_id')
            ->whereTime('c.create_time', $time)
            ->join('phone p', 'c.phone_id = p.id')
            ->join('country cy', 'p.country_id = cy.id')
            ->join('warehouse w', 'p.warehouse_id = w.id')
            ->field(['c.phone_id', 'c.create_time', 'c.ip' ,'p.phone_num', 'cy.title', 'w.url'])
            ->select();
        return $result;
    }

    //根据日期查询总点击
    public function clickCount($time){
        $result = self::where('phone_id', '>', 0)
            ->whereTime('create_time', $time)
            ->count();
        return $result;
    }

    //统计每个号码前一天的点击总量
    public function searchClickNumber($phone_id, $time){
        $result = self::where('phone_id', '=', $phone_id)
            ->whereTime('create_time', $time)
            ->count();
        return $result;
    }
}