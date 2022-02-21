<?php

namespace app\common\exception;


use Exception;
use think\exception\Handle;
use think\facade\Config;
use think\facade\Request;

class HandleException extends Handle
{
    protected $code;
    protected $msg;
    protected $errorCode;

    public function render(Exception $e)
    {
        if ($e instanceof BaseException) {
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        } else {
            if (Config::get('app_debug')) {
                return parent::render($e);
            }
            $this->code = 500;
            $this->msg = '系统故障';
            $this->errorCode = 999;
        }
        $request = Request::instance();
        $result = [
            'msg' => $this->msg,
            'error_code' => $this->errorCode,
            'request_url' => $request = $request->root(true) . $request->url(),
        ];
        return json($result, $this->code);
    }
}