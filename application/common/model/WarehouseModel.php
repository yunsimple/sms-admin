<?php

namespace app\common\model;


class WarehouseModel extends BaseModel
{
    protected $hidden = ['update_time', 'delete_time'];

    public function allData(){
        $result = self::where('show', '=', 1)
            ->order('id', 'desc')
            ->select();
        return $result;
    }
    //后台管理调用所有
    public function allWarehouse($page, $limit){
        $result = self::order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }

    //新增数据
    public function createWareHouse($data){
        $result = self::create($data);
        return $result->id;
    }

    //查询值是否存在
    public function search($url){
        $result = self::where('url', '=', $url)
            ->select();
        return $result->count();
    }

    //更改是否显示/在线
    public function check01($id, $value, $field){
        $result = self::where('id', '=', $id)
            ->update([$field => $value]);
        return $result;
    }
    
    //获取所有
    public function countNumber(){
        $result = self::select();
        return $result;
    }
    
    //获取隐藏仓库
    public function countHidden(){
        $result = self::where('show', '=', 0)->count();
        return $result;
    }
    
    //查询所有在线，需要图表显示的项目
    public function getOnline(){
        $result = self::where('show', '=', 1)
            ->select();
        return $result;
    }
}