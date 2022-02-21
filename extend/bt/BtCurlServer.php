<?php

namespace bt;


use think\facade\Request;

class BtCurlServer
{
    private $BT_KEY = "SCHf1OgZqDxyhUD7fCbvYZKfcOgbNGFg";  //广东2区蜘蛛接口密钥
    private $BT_PANEL = "";	   //面板地址

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

    //示例取面板日志
    public function site($ip, $old_ip){
    	$ip_arr = explode(':', $old_ip);
    	if (count($ip_arr) == 2){
    		$old_ip = $ip_arr[0];
    	}
        //拼接URL地址
        $this->BT_PANEL = "http://" . $old_ip . ':19490';
        $url = $this->BT_PANEL.'/site?action=AddDomain';

        //准备POST数据
        $p_data = $this->GetKeyData();		//取签名
        $p_data['domain'] = $ip . ':39008';
        $p_data['webname'] = 'spider.bilulanlv.com';
        $p_data['id'] = 11;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$p_data);
        $result_arr = json_decode($result,true);
        if($result_arr['status']){
        	$this->delete($ip_arr[0], $ip_arr[1]);
        }
        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    //删除绑定域名
    public function delete($old_ip, $old_port){
    	 //拼接URL地址
        $this->BT_PANEL = "http://" . $old_ip . ':19490';
        $url = $this->BT_PANEL.'/site?action=DelDomain';

        //准备POST数据
        $p_data = $this->GetKeyData();		//取签名
        $p_data['domain'] = $old_ip;
        $p_data['webname'] = 'spider.bilulanlv.com';
        $p_data['id'] = 11;
        $p_data['port'] = $old_port;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$p_data);
    }
  
}