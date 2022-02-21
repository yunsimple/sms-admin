<?php

namespace app\index\controller;


use think\Controller;
use app\common\model\PhoneModel;

class CheckController extends Controller
{
    public function index(){
        $ip = real_ip();
        $phone_model = new PhoneModel();
        $phone = $phone_model->appGetPhone(null, 1, 4);
        $this->assign('data', $phone);
        $this->assign('ip', $ip);
        return $this->fetch();
    }
}