<?php

namespace app\common\controller;

use app\common\model\ClickModel;
use think\Controller;

class ClickController extends Controller
{
    public function click($from, $page = 'index', $phone_id = 0){
        $data['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $data['ip'] = $_SERVER["REMOTE_ADDR"];
        $data['phone_id'] = $phone_id;
        $data['page'] = $page;
        $data['from'] = $from;
        (new ClickModel())->click($data);
    }
}