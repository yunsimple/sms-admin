<?php

namespace bt;


class Bt
{
    private $BT_KEY = "SCHf1OgZqDxyhUD7fCbvYZKfcOgbNGFg";  //接口密钥
    private $BT_PANEL = "https://95.179.247.11:20520/";	   //当前服务器

    //如果希望多台面板，可以在实例化对象时，将面板地址与密钥传入
    public function __construct($bt_panel = null,$bt_key = null){
        $this->BT_PANEL = 'https://' . $_SERVER['SERVER_ADDR'] . ':20520/';
        //if($bt_panel) $this->BT_PANEL = $bt_panel;
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

    //防火墙拉黑
    public function fireWall($ip, $info){
        //拼接URL地址
        $url = $this->BT_PANEL.'/firewall?action=AddDropAddress';

        //准备POST数据
        $p_data = $this->GetKeyData();		//取签名
        $p_data['port'] = $ip;
        $p_data['type'] = 'address';
        $p_data['ps'] = $info;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$p_data);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    //删除防火墙ip
    public function DelDropAddress($id, $port){
        //拼接URL地址
        $url = $this->BT_PANEL.'/firewall?action=DelDropAddress';

        //准备POST数据
        $p_data = $this->GetKeyData();		//取签名
        $p_data['port'] = $port;
        $p_data['id'] = $id;

        //请求面板接口
        $result = $this->HttpPostCookie($url,$p_data);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
    
    //获取防火墙信息列表
    public function getFireWallPage($page, $limit){
        //拼接URL地址
        $url = $this->BT_PANEL.'/data?action=getData';

        //准备POST数据
        $p_data = $this->GetKeyData();		//取签名
        $p_data['tojs'] = 'firewall.get_list';
        $p_data['table'] = 'firewall';
        $p_data['limit'] = $limit;
        $p_data['p'] = $page;
        $p_data['search'] = '';
        $p_data['order'] = 'id desc';

        //请求面板接口
        $result = $this->HttpPostCookie($url,$p_data);

        //解析JSON数据
        $data = json_decode($result,true);
        return $data;
    }
}