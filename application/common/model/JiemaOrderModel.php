<?php


namespace app\common\model;


class JiemaOrderModel extends BaseModel
{

    //关联模型
    public function jiemaproject(){
        return $this->belongsTo('JiemaProjectModel', 'project_id', 'pid');
    }

    //根据页数查询流水
    public function getPageList($user, $page = 1, $limit = 15){
        $result = self::with('jiemaproject')
            ->where('user', '=', $user)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }

    //后台获取全部
    public function getAllList($page = 1, $limit = 15){
        $result = self::with('jiemaproject')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }

    //获取总条数
    public function getCount($user){
        $result = self::where('user', '=', $user)
            ->count();
        return $result;
    }
}