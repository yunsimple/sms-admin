<?php

namespace app\api\controller;


use think\Controller;
use bt\BtEmailServer;
use think\facade\Lang;
use think\facade\Session;
use think\facade\Validate;
use app\common\controller\RedisController;

class MailController extends Controller
{
    protected $middleware = [
        'RecaptchaClick' => ['only' => ['emailGet', 'emailApply']],
    ];
    
    //获取emal
    public function emailGet()
    {
        //return show('System upgrading...','', 4000);
        $email = input('post.email');
        $validate = Validate::checkRule($email, 'must|email|max:30|min:10');
        if (!$validate){
            return show('传递参数异常:', $email, 4000);
        }
        if ($email == 'admin@yinsiduanxin.com'){
            return show('传递参数异常:', $email, 4000);
        }
        $bt = new BtEmailServer();
        $result = $bt->getEmail($email);
        if ($result['status']){
            if (count($result['data']) > 0){
            	//判断是否存在需要过滤的邮件发布商
                $str = [
                    'alipay@mail.alipay.com',
                    'service@mc.mail.taobao.com'
                ];
                foreach ($result['data'] as $key => $value){
                	(new RedisController())->redisNumberNoTime('mail_receive_total');
                    foreach ($str as $k => $v){
                        $num = stristr($value['from'], $v);
                        if ($num){
                            $result['data'][$key]['html'] = '抱歉，不能提供这个验证码，请勿用于非法用途！';
                        }
                    }
                }
                return show('获取邮件成功', $result['data']);
            }else{
                return show('服务器还未收到邮件','', 1);
            }

        }else{
            return show('获取失败:', $email, 4000);
        }
    }
    
    //后台获取admin
    public function emailAdminGet()
    {
        $email = input('post.email');
        $validate = Validate::checkRule($email, 'must|email|max:30|min:10');
        if (!$validate){
            return show('传递参数异常:', $email, 4000);
        }
        $bt = new BtEmailServer();
        $result = $bt->getEmail($email);
        if ($result['status']){
            if (count($result['data']) > 0){
                return $result['data'];
            }
        }else{
            return false;
        }
    }

    //指字用户名申请emal
    public function emailApply()
    {
        $user = trim(input('post.email_name'));
        $site = trim(input('post.site'));
        if (empty($user)){
            $user = $this->getEmailUser(5, 4);
        }else{
            $validate = Validate::checkRule($user, 'must|alphaDash|max:15|min:6');
            if(!$validate){
                return show('传递参数异常:', $user, 4000);
            }
        }
        $bt = new BtEmailServer();
        $result = $bt->emailApply($user, $user . $site);
        if ($result['status']){
            Session::set('email', $user . $site);
            (new RedisController())->redisNumberNoTime('mail_user_total');
            return show('申请成功', $user . $site);
        }else{
            return show('申请失败，请换个帐号试试', $result, 4000);
        }
    }

    //删除email帐户
    public function emailUserDelete()
    {
        $email = input('post.email');
        $transpond_email = input('post.transpond_email');
        $validate = Validate::checkRule($email, 'must|email|max:50|min:10');
        if (!$validate){
            return show(Lang::get('mail_alert_abnormal'), $email, 4000);
        }
        if ($email == 'admin@yinsiduanxin.com'){
            return show('传递参数异常:', $email, 4000);
        }
        $bt = new BtEmailServer();
        if ($transpond_email){
            $validate = Validate::checkRule($transpond_email, 'email|max:50|min:10');
            if (!$validate){
                return show(Lang::get('mail_alert_abnormal'), $transpond_email, 4000);
            }
            $bt->deleteTranspondEmail('recipient', $email, $transpond_email);
        }
        $result = $bt->emailUserDelete($email);
        if ($result['status']){
            Session::delete('email');
            return show($result['msg']);
        }else{
            return show('邮箱销毁失败，请重试:', $email, 4000);
        }
    }

    //根据页数获取邮箱
    public function getMailBoxs($domain){
        $page = 1;
        $size = 1;
        $bt = new BtEmailServer();
        //获取有多少条数据
        $result = $bt->getMailBoxs($domain, $page, $size);
        preg_match('/共(\d+)条/', $result['page'], $count);
        $count = $count[1];
        //dump($count);
        if ($count < 50){
            return '邮箱用户才' . $count;
        }
        $result = $bt->getMailBoxs($domain, $page, $count);
        $result = array_splice($result['data'], intval($count * 0.1));
        //dump($result);die;
        if (count($result) > 0){
            $result = $this->deleteMailBatch($result);
            return ['del_count'=>$result, 'count'=>$count];
        }else{
            return false;
        }
    }

    //批量删除邮箱，供后台批量删除
    public function deleteMailBatch($email_array = array()){
        $bt = new BtEmailServer();
        $count = count($email_array);
        for ($i = 0; $i < $count; $i++){
        	if ($email_array[$i]['is_admin'] != 1 ){
                $bt->emailUserDelete($email_array[$i]['username']);
                //dump($email_array[$i]['created']);
            }
        }
        return $count;
    }

    //生成邮件用户名
    private function getEmailUser($letter_length, $number_length)
    {
        $str = null;
        $str_letter = "abcdefghijklmnopqrstuvwxyz";
        $str_number = "1234567890";
        $max_letter = strlen($str_letter) - 1;
        $max_number = strlen($str_number) - 1;
        for ($i = 0; $i < $letter_length; $i++) {
            $str .= $str_letter[rand(0, $max_letter)];
        }
        for ($i = 0; $i < $number_length; $i++) {
            $str .= $str_number[rand(0, $max_number)];
        }
        return $str;
    }


    /**
     * 设置邮件转发地址
     */
    public function setTranspondEmail(){
		return show('此功能暂时关闭', '', 4000);
        $email = input('post.email');
        $transpond_email = input('post.transpond_email');
        $email_arr = explode('@', $email);
        if (count($email_arr) == 2){
            $email_domin = $email_arr[1];
        }else{
            return show(Lang::get('mail_alert_abnormal'), $email, 4000);
        }
        /*//判断是否重复
        $transpond = $this->checkTranspondEmail('recipient', $email);
        if ($transpond){
            return show(Lang::get('mail_main_transpond_repetition'), $email, 4000);
        }*/
        $validate = \think\Validate::make([
            'email' => 'must|email|max:30|min:10',
            'transpond_email' => 'must|email|max:30|min:10',
            'domain' => 'must|[a-z0-9]+\.com|max:30'
        ]);
        $data = [
            'email' => $email,
            'transpond_email' => $transpond_email,
            'domain' => $email_domin
        ];
        if (!$validate->check($data)){
            return show(Lang::get('mail_alert_abnormal'), $validate->getError(), 4000);
        }
        $bt = new BtEmailServer();
        $result = $bt->setTranspondEmail('recipient', $email_domin, $email, $transpond_email);
        //dump($result);
        if ($result['msg'] == '被转发用户已经存在'){
            return show(Lang::get('已经存在转发地址'), $validate->getError(), 4000);
        }
        if ($result['status']){
            return show(Lang::get('mail_alert_transpond_email_success'), $transpond_email);
        }else{
            return show(Lang::get('mail_alert_transpond_email_failed'), $validate->getError(), 4000);
        }
    }
    

    /**
     * 检查设置的转发邮件是否已经存在
     * @param $type  'sender' 发送 or 'recipient' 接收
     * @param $email
     * @return string
     */
    public function checkTranspondEmail($type, $email){
        $transpond_email_list = (new BtEmailServer())->getTranspondEmailList();
        dump($transpond_email_list);
        $transpond_email = '';
        foreach ($transpond_email_list[$type] as $value){
            if ($value['user'] == $email){
                $transpond_email = $email;
                break;
            }
        }
        return $transpond_email;
    }
}