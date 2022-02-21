<?php


namespace app\common\model;


class FeedbackModel extends BaseModel
{
    //protected $connection = 'db_master_write';
    
	public function getTypeAttr($value)
    {
        $status = [1=>'反馈',2=>'<font color="red">举报</font>'];
        return $status[$value];
    }
	
    public function insertFeedback($data)
    {
        $result = self::create($data);
        return $result->id;
    }

    //查询当日该ip提交条数
    public function searchTodayIP($ip){
        $result = self::where('ip', '=', $ip)
            ->whereTime('create_time', 'today')
            ->count();
        return $result;
    }

    //后台获取列表
    public function adminList($page, $limit){
        $result = self::page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return $result;
    }

    //获取全部数量
    public function getCount(){
        $result = self::count();
        return $result;
    }

    //后台批量删除
    public function deleteMany($id){
        $result = self::destroy($id, true);
        return $result;
    }

    //后台根据关键字获取指定日期数据
    public function getDateData($time){
        $result = self::whereTime('create_time', $time)->count();
        return $result;
    }
}