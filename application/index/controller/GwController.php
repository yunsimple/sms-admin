<?php

namespace app\index\controller;

use app\common\controller\ClickController;
use app\common\controller\RedisController;
use app\common\model\PhoneModel;
use app\index\validate\IndexValidate;
use think\facade\Request;

class GwController extends BaseController
{
    public function index(){
        $data = input('param.');
        $phone_num = input('param.phone_num');
        $page = input('param.page');
        //验证数据
        $phone_model = new PhoneModel();
        $validate = new IndexValidate();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if ($phone_num) {
            //return $this->error('搜索暂时关闭');
            $result = $phone_model->getPhone($phone_num);
            if ($result->isEmpty()) {
                return $this->error('没有搜索到您要找的号码');
            }
            //(new ClickController())->click(1, 'search',  $result->getCollection()[0]['id']);
        } else {
            //写入redis缓存
            $redis = new RedisController();
            if (empty($page)){
                $page = 1;
            }
            $redis_value = $redis->redisCheck(Request::subDomain() . '_web_gw_' . $page);
            if($redis_value){
                $result = unserialize($redis_value) ;
            }else{
                $result = $phone_model->getPartPhoneNum('gw');
                (new RedisController())->redisSetCache(Request::subDomain() . '_web_gw_' . $page, serialize($result));
            }
            //(new ClickController())->click(1, 'gw');
        }
//        $count['all'] = $phone_model->getCountNuber();
//        $count['offline'] = $phone_model->offlineNumber();
//        $count['week'] = $phone_model->monthCreateNuber();
//        $this->assign('count', $count);
		$page = $result->render();
        $result = $result->toArray();
        if (count($result['data']) > 3){
            array_splice($result['data'], 3, 0, 'Adsense');
        }
        if (count($result['data']) > 9){
            array_splice($result['data'], 11, 0, 'Adsense');
        }
        $this->assign('page', $page);
        $this->assign('data', $result['data']);
        $this->assign('title', '美国,英国,加拿大,缅甸,德国,东南亚,爱沙尼亚,菲律宾,印度尼西亚,澳大利亚等国外免费手机号码短信验证码在线接收平台');
        return $this->fetch('index/index');
    }

}