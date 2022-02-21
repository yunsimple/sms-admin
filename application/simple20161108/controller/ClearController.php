<?php

namespace app\simple20161108\controller;


use app\common\controller\RedisController;

class ClearController extends BaseController
{
    //redis页
    public function index(){
        $result = (new RedisController())->getCacheRedis();
        $this->assign('redis', $result);
        return $this->fetch('redis');
    }
    //采集失败号码页
    public function errorPhone(){
        $result = (new RedisController())->getRedissValue('error');
        $this->assign('redis', $result);
        return $this->fetch('error_phone');
    }

    //异常IP页
    public function abnormalIP(){
        $result = (new RedisController())->getRedissValue('ip');
        $impose_ip = (new RedisController())->getRedissValue('impose');
        $robot_ip = (new RedisController())->getRedissValue('robot');
        $this->assign('redis', $result);
        $this->assign('impose_ip', $impose_ip);
        $this->assign('robot_ip', $robot_ip);
        return $this->fetch('abnormal_ip');
    }

    //
    public function clearRedis(){
        $data = input('param.');
        $result = (new RedisController())->delRedis($data);
        if (!$result){
            return show('删除Redis失败,请重新再试','', 4000);
        }
        return show('删除Redis成功', $result);
    }
}