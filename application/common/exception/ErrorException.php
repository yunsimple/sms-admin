<?php
namespace app\common\exception;


class ErrorException extends BaseException
{
    public $code = 202;
    public $msg = '服务器请求异常,请尝试使用其他号码';
    public $errorCode = 4000;
}