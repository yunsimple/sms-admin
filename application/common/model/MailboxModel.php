<?php


namespace app\common\model;


class MailboxModel extends BaseModel
{
    //protected $connection = 'db_master_write';
    
/*    public function getBefromAttr($value)
    {
        $status = [2=>'my'];
        return $status[$value];
    }*/
    
    
    public function insertMailbox($data)
    {
        $result = self::create($data);
        return $result->id;
    }

    public function search($mailbox){
        $result = self::where('mailbox', '=', $mailbox)->value('id');
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
    
    //获取所有邮件号码发送邮件
    public function getALLMail(){
        $result = self::order('id', 'desc')
            ->where('send', 1)
            ->column('mailbox');
        return $result;
    }

    //后台根据关键字获取指定日期数据
    public function getDateData($time){
        $result = self::whereTime('create_time', $time)->count();
        return $result;
    }

    //后台区间查询报表统计
    public function getBetweenTime($date){
        if (is_array($date)){
            $result = self::whereBetweenTime('create_time', $date[0], $date[1])->count();
        }else{
            $result = self::whereBetweenTime('create_time', $date)->count();
        }
        return $result;
    }
}