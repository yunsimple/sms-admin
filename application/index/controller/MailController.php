<?php

namespace app\index\controller;


use think\facade\Session;

class MailController extends BaseController
{
    public function index(){
        $email = Session::get('email');
        if ($email){
            $this->assign('email', $email);
        }
        return $this->fetch();
    }
}