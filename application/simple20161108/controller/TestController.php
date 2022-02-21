<?php

namespace app\simple20161108\controller;

use app\common\controller\SendSmsController;
use app\common\controller\MailController;
use think\Controller;

class TestController extends Controller
{
    public function index(){
    	(new MailController())->sendMail(1888888);
    }



}