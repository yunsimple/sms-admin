<?php

namespace app\common\model;

use think\Db;
use think\facade\Request;
use app\common\controller\RedisController;
use Overtrue\Pinyin\Pinyin;

class CollectionMsgModel extends BaseModel
{
	protected $connection = 'db_master_write';
	
    //批量新增
    public function batchCreate($data){
/*    	if ($conn){
			$result = Db::connect([
			    // 数据库类型
			    'type'        => 'mysql',
			    // 服务器地址
			    'hostname'    => '127.0.0.11',
			    // 数据库名
			    'database'    => 'sms_collection',
			    // 数据库用户名
			    'username'    => 'sms_collection',
			    // 数据库密码
			    'password'    => 'njJMaxA2d5TGEnyy',
			    // 数据库连接端口
			    'hostport'    => '50066',
			    // 数据库编码默认采用utf8
			    'charset'     => 'utf8mb4',
			])->table('collection_msg')->insertAll($data);
			return $result;
    	}*/
        //$result = Db::connect('db_collection_nonlocal_config')->insertAll($data);
        $result = self::saveAll($data);
        return $result;
    }

    /**
     * message分页数据
     */
    public function getPageData($phone_id, $phone_num){
        $result = self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->paginate(20, 200, [
                'page'=>input('param.page')?:1,
                'path'=>Request::domain().'/message/'.$phone_num.'/[PAGE].html'
            ]);
        return $result;
    }
    
    /**
     * message分页数据
     */
    public function messagePage($phone_id, $phone_num){
        $result = self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->paginate(20, 200, [
                'page'=>Request::param('page')?:1,
                'path'=>'/'.Request::param('country').'-phone-number/verification-code-'.$phone_num.'/[PAGE].html'
            ]);
        return $result;
    }

    /**
     * 12-29重构 获取历史短信记录
     * @param $phone_id
     * @param $total 显示的总数，用以确定分页数量
     */
    public function getHistorySms($phone_id, $phone_num, $total_num){
        return self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->cache(86400)
            ->paginate(20, (int)$total_num, [
                'page'=>Request::param('page')?:1,
                'path'=>Request::domain()."/receive-sms-from-".Request::param('country')."/".$phone_num."/[PAGE]"
            ]);
    }

    public function getMessagePage($phone_id, $page){
        $result = self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->page($page, 20)
            ->cache($phone_id . $page, 1800)
            ->select();
        return $result;
    }
    
    
        /**
     * message分页数据
     */
    public function getPageDataMytempsms($phone_id, $phone_num){
        $result = self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->paginate(20, 200, [
                'page'=>Request::param('page')?:1,
                'path'=>'/receive-sms-online/'.Request::param('country').'-phone-number-'.$phone_num.'/[PAGE].html'
            ]);
        return $result;
    }

    /**
     * message分页数据
     */
    public function messagePageMytempsms($phone_id, $phone_num){
        $result = self::where('phone_id', '=', $phone_id)
            ->order('id', 'desc')
            ->paginate(20, 200, [
                'page'=>Request::param('page')?:1,
                'path'=>'/receive-sms-online/'.Request::param('country').'-phone-number-'.$phone_num.'/[PAGE].html'
            ]);
        return $result;
    }
    
     /**
     * 获取项目短信，如果redis不存在，就从数据库读取。有效
     */
    public function getProjectMessage($project){
        $result = Db::connect('db_master_write')
            ->table('collection_msg')
            ->where('url', '=', $project)
            ->field('p.id,p.uid,p.phone_num,m.smsContent,m.url,c.en_title')
            ->alias('m')
            ->join(['phone'=>'p'], 'm.phone_id = p.id')
            ->join(['country'=>'c'], 'p.country_id = c.id')
            ->group('p.phone_num')
            ->limit(20)
            ->cache(86400)
            ->select();
        return $result;
    }
    
    //序列化处理
    function mb_unserialize($str) {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s',function($match){return 's:'.strlen($match[2]).':"'.$match[2].'";';},$str);
    }
}