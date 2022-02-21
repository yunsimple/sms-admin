<?php

namespace bt;


use think\facade\Request;

class BtEmailServer
{
    private $BT_KEY = "scRhvrY5POwPAclMRnEWOHWu5vECiBEh";  //接口密钥
    private $BT_PANEL = "https://23.239.2.123:20520/";	   //当前服务器

    //如果希望多台面板，可以在实例化对象时，将面板地址与密钥传入
    public function __construct($bt_key = null){
        if($bt_key) $this->BT_KEY = $bt_key;
    }

    /**
     * 构造带有签名的关联数组
     */
    private function GetKeyData(){
        $now_time = time();
        $p_data = array(
            'request_token'	=>	md5($now_time.''.md5($this->BT_KEY)),
            'request_time'	=>	$now_time
        );
        return $p_data;
    }


    /**
     * 发起POST请求
     * @param String $url 目标网填，带http://
     * @param Array|String $data 欲提交的数据
     * @return string
     */
    private function HttpPostCookie($url, $data,$timeout = 60)
    {
        //定义cookie保存位置
        $cookie_file='./'.md5($this->BT_PANEL).'.cookie';
        if(!file_exists($cookie_file)){
            $fp = fopen($cookie_file,'w+');
            fclose($fp);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //新增邮箱
    public function emailApply($user, $email){
        //拼接URL地址
        $url = $this->BT_PANEL.'plugin?action=a&name=mail_sys&s=add_mailbox';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['quota'] = '10 MB';
        $params['username'] = $email;
        $params['password'] = 'q(s1DF!dlk)C3!rE43#';
        $params['full_name'] = $user;
        $params['is_admin'] = 0;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }

    //获取邮件
    public function getEmail($email){
        //拼接URL地址
        $url = $this->BT_PANEL.'plugin?action=a&name=mail_sys&s=get_mails';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['username'] = $email;
        $params['p'] = 1;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }

    //删除帐户
    public function emailUserDelete($user){
        //拼接URL地址
        $url = $this->BT_PANEL.'plugin?action=a&name=mail_sys&s=delete_mailbox';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['username'] = $user;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    //获取帐户
    public function getMailBoxs($domain, $page, $size){
        //拼接URL地址
        $url = $this->BT_PANEL.'plugin?action=a&name=mail_sys&s=get_mailboxs';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['domain'] = $domain;
        $params['p'] = $page;
        $params['size'] = $size;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    
    /**
     * 获取转发邮箱列表
     * @return mixed
     */
    public function getTranspondEmailList(){
        //拼接URL地址
        $url = $this->BT_PANEL.'plugin?action=a&name=mail_sys&s=get_mail_forward';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['action'] = 'a';
        $params['name'] = 'mail_sys';
        $params['s'] = 'get_mail_forward';

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }

    /**
     * 设置邮件转发地址
     * @param $type  'sender' 发送 or 'recipient' 接收
     * @param $domain
     * @param $email
     * @param $transpond_email
     * @return mixed
     */
    public function setTranspondEmail($type, $domain, $email, $transpond_email){
        //拼接URL地址
        $url = $this->BT_PANEL.'/plugin?action=a&name=mail_sys&s=set_mail_forward';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['active'] = 1;
        $params['domain'] = $domain;
        $params['user'] = $email;
        $params['forward_user'] = $transpond_email;


        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }

    /**
     * 删除转发邮箱
     * @param $type 'sender' 发送 or 'recipient' 接收
     * @param $email
     * @param $transpond_email
     * @return mixed
     */
    public function deleteTranspondEmail($type, $email, $transpond_email){
        //拼接URL地址
        $url = $this->BT_PANEL.'/plugin?action=a&name=mail_sys&s=delete_mail_forward';
        //准备POST数据
        $params = $this->GetKeyData();		//取签名
        $params['user'] = $email;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    public function sendEmail($mail_from, $mail_to, $subject, $content){
        //拼接URL地址
        //$url = $this->BT_PANEL.'/plugin?action=a&name=mail_sys&s=send_mail';
        //$url = 'http://10.28.70.218:19490/mail_sys/send_mail_http.json';
        
        $url = $this->BT_PANEL.'/mail_sys/send_mail_http.json';
        //$url = 'http://68.175.161.142:19490/mail_sys/send_mail_http.json';
        //准备POST数据
        //$params = $this->GetKeyData();		//取签名
        $params['password'] = 'Ce15E6!7Pp54@#';
        $params['mail_from'] = $mail_from;
        $params['mail_to'] = $mail_to;
        $params['subject'] = $subject;
        $params['content'] = $content;
        $params['subtype'] = 'html';
/*        $params = $this->GetKeyData();		//取签名
        $params['smtp_server'] = 'localhost';
        $params['mail_from'] = $mail_from;
        $params['mail_to'] = $mail_to;
        $params['subject'] = $subject;
        $params['content'] = $content;
        $params['subtype'] = 'html';*/
        //请求面板接口
        $result = curl_post($url,$params);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
}