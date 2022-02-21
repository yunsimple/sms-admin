<?php


namespace app\index\controller;


class NoFoundController extends BaseController
{
    public function index(){
        return $this->fetch();
    }
}