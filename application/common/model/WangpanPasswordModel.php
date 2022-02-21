<?php


namespace app\common\model;


class WangpanPasswordModel extends BaseModel
{
    public function add($data){
        $result = self::create($data);
        return $result->id;
    }
    
    public function searchUrl($url){
        $result = self::where('url', '=', $url)
            ->find();
        return $result;
    }
}