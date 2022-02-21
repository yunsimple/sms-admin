<?php

namespace app\index\controller;


use app\api\controller\ApiController;
use app\common\controller\ReCaptchaController;
use app\common\controller\RedisController;
use app\common\model\CollectionMsgModel;
use app\common\model\PhoneModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\Validate;

class MessageController extends BaseController
{
    public function index(){
        $phone_num = Request::param('phone_num');
        $validate = Validate::checkRule($phone_num, 'must|number|max:12|min:7');
        if(!$validate){
            return $this->error('传递参数异常');
        }
        $phone_model = new PhoneModel();
        $result = $phone_model->getPhoneNum($phone_num);
        if(!$result){
            return $this->error('号码不存在');
        }
/*        if($result['online'] == 0){
            return $this->error('号码离线中,请尝试使用其他号码');
        }*/
        $result_sms = (new ApiController())->getSMS($phone_num);
        $result_sms = json_decode($result_sms->getContent(), true);
        if($result_sms['error_code'] != 0){
            return $this->error($result_sms['msg']);
        }
        //获取分页,存放在redis里面
        $redis = new RedisController();
        $redis_message_page = $redis->redisCheck('_message_page_' . $phone_num);
        if (!$redis_message_page){
            $list = (new CollectionMsgModel())->getPageData($result['id'], $phone_num);
            $page = $list->render();
            //写入redis
            $redis->redisSetCache('_message_page_' . $phone_num, $page, 86400);
        }else{
            $page = $redis_message_page;
        }
        $result_sms = $result_sms['data'];
        if (count($result_sms) > 2){
            array_splice($result_sms, 2, 0, 'Adsense');
        }
        if (count($result_sms) > 14){
            array_splice($result_sms, 10, 0, 'Adsense');
        }
        $this->assign('page', $page);
        $this->assign('data', $result_sms);
        $this->assign('phone_num', $phone_num);
        $this->assign('bh', $result['country']['bh']);
        $this->assign('country', $result['country']['title']);
        $this->assign('online', $result['online']);
        return $this->fetch();
    }

        /**
     * 获取message历史分页数据
     */
    public function getPageData($phone_num, $page){
        $phone_info = (new PhoneModel())->getPhoneNum($phone_num);
        $result = (new CollectionMsgModel())->getPageData($phone_info['id'], $phone_num);
        $page = $result->render();
        $result_data = $result->toArray();
        $result = array();
        for ($i = 0; $i < count($result_data['data']); $i++){
            $msg_data = unserialize($result_data['data'][$i]['content']);
            $result[$i]['smsDate'] = $msg_data['smsDate'];
            //$result[$i]['PhoNum'] = $msg_data['PhoNum']; //smsonline部分号码没有存入PhoNum值
            $result[$i]['smsNumber'] = $msg_data['smsNumber'];
            $result[$i]['smsContent'] = $msg_data['smsContent'];
        }
        $this->assign('page', $page);
        $this->assign('phone_num', $phone_num);
        $this->assign('bh', $phone_info['country']['bh']);
        $this->assign('country', $phone_info['country']['title']);
        $this->assign('data', $result);
        $this->assign('online', $phone_info['online']);
        return $this->fetch('index');
    }
    
        //前台报告无法收到短信
    public function report(){
        $phone_num = input('post.phone_num');
        $validate = Validate::checkRule($phone_num, 'must|number|max:15|min:6');
        if(!$validate){
            return show('传递参数异常', '', 4000);
        }
        //把提交的号码保存进入redis. respore_1814266666
        $redis = new RedisController();
        $return = $redis->redisNumber('report_' . $phone_num, 172800);
        if (!$return){
            return show('提交反馈失败', '', 4000);
        }
        return show('已成功反馈给站长');
    }

    //前台随机获取一个号码显示
    public function random(){
        $phone_model = new PhoneModel();
        $phone_num = $phone_model->getRandom();
        if (!$phone_num){
            return show('获取随机号码失败', '', 4000);
        }
        return show('获取随机号码成功', $phone_num);
    }

}