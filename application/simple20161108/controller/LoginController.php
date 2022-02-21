<?php

namespace app\simple20161108\controller;

use app\simple20161108\validate\LoginValidate;
use app\common\model\UserModel;
use geetest\Geetest;
use think\captcha\Captcha;
use think\Controller;
use think\facade\Config;
use think\facade\Session;
use think\Request;

class LoginController extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function login()
    {
        $data = input('post.');
        $validate = new LoginValidate();
        if (!$validate->check($data)) {
            return show($validate->getError(), '', 4000);
        }

        //判断用户是否存在
        $user_info = (new UserModel())->getUserInfo('yunzhi');
        if (md5($user_info['name']) !== $data['username']) {
            return show('用户不存在', '', 4000);
        }

        if ($user_info['type'] != 1){
            return show('用户不存在', '', 4000);
        }
        $p = md5($data['password'] . $user_info['solt']);
        if ($p == $user_info['password']) {
            Session::set('user_admin', $user_info['name']);
            return show('登陆成功', ['url' => '/']);
        } else {
            return show('用户名或密码不正确', '', 4000);
        }
    }

    public function loginOut()
    {
        Session::delete('user_admin');
        $this->redirect('Login/index');
    }

    //thinkphp自带文字验证
    public function verify(){
        $config =    [
            // 验证码字体大小
            'fontSize' => 40,
            // 验证码位数
            'length' => 4,
            //验证码杂点
            'useNoise' => true,
            //是否画混淆曲线
            'useCurve' => true,
            //验证码过期时间（s）
            'expire' => 300,
            //背景图片
            'useImgBg' => false,
            //背景颜色
            //'bg' => [243, 251, 254]
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    //GT校验
    public function geetestSession()
    {
        $data = array(
            "user_id" => $_SERVER['REQUEST_TIME'], # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => '127.0.0.1' # 请在此处传输用户请求验证时所携带的IP
        );

        $GtSdk = new Geetest(Config::get('config.geetest.captcha_id'), Config::get('config.geetest.private_key'));
        $status = $GtSdk->pre_process($data, 1);
        Session::set('gtserver', $status);
        Session::set('user_id', $data['user_id']);
        echo $GtSdk->get_response_str();
    }

    //GT后端登陆检查,判断走哪个通道
    public function validateGeetest($data)
    {
        $value = array(
            "user_id" => Session::get('user_id'), # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => '127.0.0.1' # 请在此处传输用户请求验证时所携带的IP
        );
        $GtSdk = new Geetest(Config::get('config.geetest.captcha_id'), Config::get('config.geetest.private_key'));
        if (Session::get('gtserver') == 1) {   //服务器正常
            $result = $GtSdk->success_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'], $value);
            if ($result) {
                return 'success';
            } else {
                return 'fail';
            }
        } else {  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($data['geetest_challenge'], $data['geetest_validate'], $data['geetest_seccode'])) {
                return 'success';
            } else {
                return 'fail';
            }
        }
    }
}
