<?php

namespace app\api\controller;

use app\api\validate\ApiValidate;
use app\common\controller\ClickController;
use app\common\controller\RedisController;
use app\common\controller\MailController;
use app\common\model\PhoneModel;
use QL\QueryList;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\Controller;
class ApiController extends Controller
{
    public function index()
    {
        return '';
    }

    //API调用获取全部号码
    public function postSmsNumber()
    {
        $page = input('post.page');
        $limit = input('post.limit');
        $region = input('post.region');
        $redis = new RedisController();
        //缓存了第一页数据
        $redis_value = $redis->redisCheck('xcx_' . $region . '_' . $page . $region);
        if ($redis_value) {
            $data = json_decode($redis_value);
        } else {
            $phone_model = new PhoneModel();
            switch ($region) {
                case 'dl':
                    $data = $phone_model->xcxPartPhoneNum('dl', $page, $limit);
                    $redis->redisSetCache('xcx_' . $region . '_' . $page, json_encode($data, true));
                    break;
                case 'gat':
                    $data = $phone_model->xcxPartPhoneNum('gat', $page, $limit);
                    $redis->redisSetCache('xcx_' . $region . '_' . $page, json_encode($data, true));
                    break;
                case 'gw':
                    $data = $phone_model->xcxPartPhoneNum('gw', $page, $limit);
                    $redis->redisSetCache('xcx_' . $region . '_' . $page, json_encode($data, true));
                    break;
                default:
                    $data = $phone_model->getAllPhoneNum($page, $limit);
                    $redis->redisSetCache('xcx_' . $page, json_encode($data, true));
            }

        }
        if (count($data) < 1) {
            return show('已经到底啦...', $data, 5002);
        }
        (new ClickController())->click(2);
        return show('获取成功', $data);
    }

    //API调用获取各地区号码总数
    public function getRegionNum()
    {
        $result = (new PhoneModel())->getRegionNum();
        if (!$result) {
            return show('获取失败', '', 4000);
        }
        return show('获取成功', $result);
    }

    //API调用查找单条信息
    public function postOneNumber()
    {
        $phone_num = input('post.phone_num');
        $data = ['phone_num' => $phone_num];
        $validate = new ApiValidate();
        if (!$validate->check($data)) {
            return show($validate->getError(), '', 4000);
        }
        $result = (new PhoneModel())->getPhoneNum($phone_num);
        if ($result) {
            $val[0] = $result;
            (new ClickController())->click(2, 'search', $result['id']);
            return show('获取成功', $val);
        } else {
            return show('没有找到您要的号码:' . $phone_num, '', 4000);
        }
    }

    /**
     * @return \think\response\Json
     */
    public function getSMS($phone = 0)
    {
        //$ip = $_SERVER["REMOTE_ADDR"];
        $ip = real_ip();
        //接口频率限制,如果10分钟内超过
        $api_redis = new RedisController();
        //$api_redis->delPrefixRedis('robot*');
        if ($phone != 0) {
            $phone_num = $phone;
        } else {
            $phone_num = input('post.phone_num');
        }
        $data = ['phone_num' => $phone_num];
        //验证数据
        $validate = new ApiValidate();
        if (!$validate->check($data)) {
            return show($validate->getError(), '', 5000);
        }
        $phone_info = (new \app\mys\controller\PhoneController())->getPhoneDetail($phone_num);

        if ($api_redis->redisCheck('impose_' . $ip)) {
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
            return show('获取成功', $result_data);
        }

        if ($phone_info['online'] == 0){
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
            return show('获取成功', $result_data);
        }

        if ($phone_info['warehouse']['title'] == 'Local' || $phone_info['warehouse']['title'] == 'ATLAS' || $phone_info['warehouse']['title'] == 'Voice'){
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
            (new RedisController())->redisNumberNoTime('success_' . $phone_info['warehouse']['title']);
            return show('获取成功', $result_data);
        }

        $warehouse_click_number = $api_redis->redisNumberMill('click_' . $phone_info['warehouse']['title'], 500);
        if ($warehouse_click_number > 1) {
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
            return show('获取成功', $result_data);
        } else {
            $curl_data = $this->curlData($phone_num, $phone_info);
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], $curl_data);
            return show('获取成功', $result_data);
        }

        //每个号码每2秒采集一次,避免重复采集
        $warehouse_phone_click_number = $api_redis->redisCheck('click_' . $phone_info['warehouse']['title'] . '_' . $phone_num);
        if ($warehouse_phone_click_number) {
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
            return show('获取成功', $result_data);
        } else {
            $curl_data = $this->curlData($phone_num, $phone_info);
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], $curl_data);
            return show('获取成功', $result_data);
        }

        //单个用户3秒才能获取一次
        $time_ip = $api_redis->redisNumber('time_' . $ip, 3);
        if ($time_ip > 1) {
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], 'skip');
        } else {
            $curl_data = $this->curlData($phone_num, $phone_info);
            $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], $curl_data);
        }

        return show('获取成功', $result_data);
    }

    //后台查询号码,无限制
    public function adminGetSMS($phone_num){
        $phone_model = new PhoneModel();
        $phone_info = $phone_model->getPhoneFind($phone_num);
        $curl_data = $this->curlData($phone_num, $phone_info);
        $result_data = $this->disposeCurlData($phone_num, $phone_info['warehouse']['title'], $curl_data);
        return show('获取成功', $result_data);
    }

    /**
     * 远程采集
     */
    public function curlData($phone_num, $phone_info)
    {
        $redis = new RedisController('sync');
        //$ip = $redis->redisSetStringValue('curl_url');
        //绑定的腾讯内网IP
        $url = $redis->redisCheck('curl_url');
        //$url = '172.30.0.3';
        $url = 'http://'.$url.'/smsapicurl';
        $param = [
            'url' => $phone_info['warehouse']['title'],
            'phone_num' => $phone_num,
            'phone_bh' => $phone_info['country']['bh'],
            'phone_id' => $phone_info['phone_id']
        ];
        $client = new Client();
        //$data = curl_post($url, $param);
        try {
            $data = $client->request('POST', $url, [
                'form_params' => $param,
                'timeout' => 20
            ])->getBody();

        } catch (RequestException $e ) {

            $data = 'failed';
        }

        if (strlen($data) < 9 ){
            $data = 'failed';
            (new RedisController())->redisNumberNoTime('failed_' . $phone_info['warehouse']['title']);
        }else{
            $data = json_decode($data, true);
            (new RedisController())->redisNumberNoTime('success_' . $phone_info['warehouse']['title']);

            //该号码成功时间，判断每个号码采集频率
            (new RedisController())->redisNumberMill('click_' . $phone_info['warehouse']['title'] . '_' . $phone_num, 3000);
        }
        return $data;
    }

    /**
     * 获取到采集的数据后的处理方式
     */
    protected function disposeCurlData($phone_num, $warehouse, $data)
    {
        //双服务器，兼容phone_click
        $api_redis = new RedisController();
        $api_redis->hIncrby('phone_click', $phone_num);
        //curl获取数据
        if (!is_array($data)) {
            //如果采集不到数据,在redis中间生成一个错误的号码次数累加,超过5次采集失败就离线号码,清除
            if ($data == 'failed') {
                $error_num = $api_redis->redisNumber('error_' . $phone_num, 3600);
                if ($error_num == 80) {
                    //错误次数过多,排序到最后-9
                    //(new PhoneModel())->check01($phone_num, 'sort', '-3');
                    //(new PhoneModel())->check01($phone_num, 'online', 0);
                    //清除redis分页缓存数据
                    $api_redis->delRedis();
                    //发送短信通知
                    (new MailController())->sendMail($phone_num, $warehouse);
                }
            }

            //如果采集失败,直接获取redis里面的值,如果redis没有值,就去数据库读取
            $redis_sync = new RedisController('sync');
            $cache_msg = $redis_sync->checkZset($phone_num);

            if (!$cache_msg) {
                return show('服务器异常,访问正在恢复中,请尝试使用其他号码...', '', 4000);
                //去数据库读取msg数据
                //TODO...
            } else {
                //redis缓存读取
                $data = $this->getMsgCache($phone_num, 19);

            }

            //return show('服务器异常,访问正在恢复中,请尝试使用其他号码...', '', 4000);
            //throw new ErrorException(['msg'=>'服务器请求异常,攻城师正在处理中,请尝试使用其他号码...']);
        } else {
            $phone_info = (new \app\mys\controller\PhoneController())->getPhoneDetail($phone_num);
            if ($phone_info['warehouse']['message_save'] == 0){
                $api_redis->del($phone_num);
            }
            //把获取到的短信加入redis集合
            $data = $this->msgRedis($phone_num, $data);
        }
        return $data;
    }

    //把获取到的短信加入redis集合
    public function msgRedis($phone_num, $data)
    {
        //获取的短信数组每条循环写入到redis里面
        //采集有序集合的方式,每条记录给一个分数
        $redis = new RedisController('master');
        $number = count($data);
        //dump($data);
        for ($i = 1; $i < $number+1; $i++) {
            $redis->zAdd($phone_num, $redis->getRedisSet('msg_' . $phone_num . '_score'), serialize($data[$number-$i]));
        }
        //如果集合内数据超过50条,就把该条数据加入队列入库处理
        $number = $redis->checkZset($phone_num);
        if ($number > 20) {
            //加入待处理列表
            $redis->setSetValue('msg_queue', $phone_num);
        }
        return $this->getMsgCache($phone_num, 20);
    }

    /**
     * redis读取号码缓存
     * @param $phone_num
     * @param int $num
     * @return array
     */
    protected function getMsgCache($phone_num, $num = 19){
        $redis = new RedisController('sync');
        $result = $redis->zRevRange($phone_num, 0, $num);
        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i] = unserialize($result[$i]);
        }
        return $data;
    }

}