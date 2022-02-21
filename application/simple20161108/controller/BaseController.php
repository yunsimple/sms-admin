<?php

namespace app\simple20161108\controller;

use think\Controller;
use think\facade\Session;

class BaseController extends Controller
{
    public function initialize(){
        $session= Session::get('user_admin');
        if(!$session){
            $this->redirect(url('Login/index'));
        }
    }
}