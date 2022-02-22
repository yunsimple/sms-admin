<?php

namespace app\simple20161108\controller;

use app\common\controller\RedisController;
use app\common\model\PhoneModel;
use bt\BtCurlServer;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Vpc\V20170312\VpcClient;
use TencentCloud\Vpc\V20170312\Models\AllocateAddressesRequest;
use TencentCloud\Vpc\V20170312\Models\DisassociateAddressRequest;
use TencentCloud\Vpc\V20170312\Models\ReleaseAddressesRequest;
use TencentCloud\Vpc\V20170312\Models\AssociateAddressRequest;
use TencentCloud\Vpc\V20170312\Models\DescribeAddressesRequest;
use TencentCloud\Vpc\V20170312\Models\DescribeTaskResultRequest;

class SettingController extends BaseController
{
    public function index(){
        $redis = new RedisController('sync');
        $old_url = $redis->redisSetStringValue('curl_url');
        $ad_switch = $redis->searchReturnValue('ad_switch');
        if ($ad_switch == 1){
            $ad_switch = 'checked';
        }else{
            $ad_switch = '';
        }
        $this->assign('ad_switch', $ad_switch);
        $this->assign('old_url', $old_url);
        return $this->fetch();
    }

    public function adSwitch(){
        $ad_switch = input('post.ad_switch');
        $redis = new RedisController('master');
        if (empty($ad_switch)){
            //关闭
            $result = $redis->setStringValue('ad_switch', 0);
            if ($result){
                return show('设置广告成功');
            }
        }else{
            //开启
            $result = $redis->setStringValue('ad_switch', 1);
            if ($result){
                return show('设置广告成功');
            }
        }
    }
    
    public function changePhoneOnlineTime(){
        $online_date = input('post.onlineDate');
        if(!$online_date){
            return show('时间参数不能为空', '', 4000);
        }
        $online_date = strtotime($online_date);
        if(!$online_date){
            return show('参数错误', '', 4000);
        }
        $redis = new RedisController('sync');
        $result = $redis->setStringValue('phone_online_time', $online_date);
        if($result){
            return show('上线时间设置成功');
        }else{
            return show('上线时间设置错误', '', 4000);
        }
    }

    /**
     * 半自动更新,需要先有一个服务器地址才行
     */
    public function changeCurlUrl($ip = '', $old_ip = ''){
        /**
         * 1.更新redis里面curl_url的值
         * 2.更新采集服务器里的绑定IP,否则不能正常访问.
         * 3.更新采集服务器的解析功能,否则bilulanlv不能正常访问.
         */
        if (empty($ip)){
           $ip = input('post.url');
           $old_ip = input('post.old_url');
        }
        $redis = new RedisController();
        //更换采集服务器后,批量更新sort为正常
        //$redis->delPrefixRedis('success_');
        $result = $redis->redisChangeStringValue('curl_url', $ip . ':39008');
        if ($result){
            //将IP绑定BT服务器
            $site_result = (new BtCurlServer())->site($ip, $old_ip);
            if ($site_result['status']){
                //更改解析
                //$dnspod_result = $this->dnspodChange($ip);
                //$dnspod_result = json_decode($dnspod_result, true);
                //if ($dnspod_result['status']['code'] == 1){
                    //更换采集服务器后,批量更新sort为正常
                    (new PhoneModel())->batchChangeSort();
                    //$redis->delPrefixRedis('success_');
                    return show('更改缓存,BT,解析全部成功');
                //}else{
                //    return show('远程更改解析失败', '', 4000);
               // }
            }else{
                return show('添加BT站点失败', $site_result, 4000);
            }
        }else{
            return show('更改Redis缓存失败', $result, 4000);
        }
    }

    //腾讯云解析远程更改
    public function dnspodChange($ip){
        $url = 'https://dnsapi.cn/Batch.Record.Modify';
        $param = [
            'login_token' => '',
            'format' => 'json',
            'record_id' => '',
            'change' => 'value',
            'change_to' => $ip
        ];
        return curl_post($url,$param);
    }

    /**
     * 全自动一键更新腾讯云EIP,采集服务器BT站点,云解析
     */
    public function changeOneCurlUrl(){
        //获取旧EIP的id和IP地址
        //新建一个EIP,并且先进行BT站点绑定
        //解绑旧的EIP,并且释放
        //绑定新的EIP到服务器上

        //获取旧eip的id
        /*$search_data = $this->searchEIP();
        if ($search_data['TotalCount'] == 2){
            $old_id = $search_data['AddressSet'][0]['AddressId'];
            //防止申请多个出现错误
            if ($old_id == 'eip-0b8qrhf2'){
                return show('新建失败,似乎只有一个弹性公网IP', '', 4000);
            }
        }else{
            return show('弹性公网IP出错,请先核对是否申请了多个EIP', '', 4000);
        }*/
         //获取旧eip的id
        $search_data = $this->searchEIP();
        if ($search_data['TotalCount'] == 2){
            $old_id = $search_data['AddressSet'][0]['AddressId'];
            //防止申请多个出现错误
        }else{
            return show('弹性公网IP出错,请先核对是否申请了多个EIP', '', 4000);
        }
        //创新新的
        $create_data = $this->createEIP();
        if (count($create_data['AddressSet']) != 1){
            return show('申请弹性公网IP出错,请先进入后台核对', '', 4000);
        }
        $new_id = $create_data['AddressSet'][0];
        $new_taskid = $create_data['TaskId'];
        $this->whileAsyn($new_taskid);
        //sleep(3);
        //查询新EIP的IP地址
        $new_ip = $this->searchEIP($new_id);
        if ($new_ip['TotalCount'] != 1){
            return show('查询新建弹性公网IP地址出错,请先进入后台核对', $new_ip, 4000);
        }
        $new_ip = $new_ip['AddressSet'][0]['AddressIp'];
        //新建新的EIP且查询到后,进行BT绑定,以及云解析更改
        $bt_data = $this->changeCurlUrl($new_ip, input('post.old_url'));
        $bt_data = json_decode($bt_data->getContent(), true);
        if ($bt_data['error_code'] != 0){
            return show($bt_data['msg'], $bt_data, 4000);
        }
        //解绑旧的,并且释放
        $jiebang_data =  $this->jiebangEIP($old_id);
        if (!$jiebang_data['TaskId']){
            return show('解绑弹性公网IP失败,请先进入后台核对', $jiebang_data, 4000);
        }
        $this->whileAsyn($jiebang_data['TaskId']);
        //sleep(3);
        $shifang_data = $this->shifangEIP($old_id);
        if (!$shifang_data['TaskId']){
            return show('释放弹性公网IP失败,请先进入后台核对', $shifang_data, 4000);
        }
        //绑定
        $server = 'ins-4a09ydr6';//采集服务器实例
        $bangding_data = $this->bangdingEIP($new_id, $server);
        if (!$bangding_data['TaskId']){
            return show('绑定弹性公网IP失败,请先进入后台核对', $bangding_data, 4000);
        }
        return show('一键换绑成功，新IP为：' . $new_ip);
    }
    
    private $sid = '*'; //广东2区
    private $skey = '*'; //广东2区
    private $region = 'ap-guangzhou';

    //创建EIP
    private function createEIP(){
        try {

            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new AllocateAddressesRequest();

            $params = '{}';
            $req->fromJsonString($params);


            $resp = $client->AllocateAddresses($req);
            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    //绑定弹性公网IP
    private function bangdingEIP($id, $server){
        try {
            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new AssociateAddressRequest();

            $params = json_encode(['AddressId' => $id, 'InstanceId' => $server]);
            $req->fromJsonString($params);


            $resp = $client->AssociateAddress($req);

            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    //查询弹性公网IP
    private function searchEIP($id = ''){
        try {
            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new DescribeAddressesRequest();

            if (empty($id)){
                $params = '{}';
            }else{
                $params = json_encode(['AddressIds' => [$id]]);
            }

            $req->fromJsonString($params);
            $resp = $client->DescribeAddresses($req);
            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    //解绑弹性公网IP
    private function jiebangEIP($id){
        try {

            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new DisassociateAddressRequest();

            $params = json_encode(['AddressId' => $id]);
            $req->fromJsonString($params);
            $resp = $client->DisassociateAddress($req);

            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    //释放弹性公网Ip
    private function shifangEIP($id){
        try {
            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new ReleaseAddressesRequest();

            $params = json_encode(['AddressIds' => [$id]]);
            $req->fromJsonString($params);
            $resp = $client->ReleaseAddresses($req);
            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            return $e;
        }
    }

    private function asynSearchEIP($taskid){
        try {

            $cred = new Credential($this->sid, $this->skey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("vpc.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new VpcClient($cred, $this->region, $clientProfile);

            $req = new DescribeTaskResultRequest();

            $params = json_encode(['TaskId' => (integer)$taskid]);
            $req->fromJsonString($params);


            $resp = $client->DescribeTaskResult($req);

            return json_decode($resp->toJsonString(), true);
        }
        catch(TencentCloudSDKException $e) {
            echo $e;
        }
    }

    protected function whileAsyn($taskid){
        $i = 0;
        while (true){
            $asyn_data = $this->asynSearchEIP($taskid);
            if ($asyn_data['Result'] == 'SUCCESS' or $i > 20){
                break;
            }
            usleep(500000);
            $i++;
        }
        if ($i > 20){
            return show('循环查询异步失败', $taskid, 4000);
        }
    }

}