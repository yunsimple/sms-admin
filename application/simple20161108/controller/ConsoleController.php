<?php

namespace app\simple20161108\controller;


use app\common\controller\RedisController;
use app\common\model\WarehouseModel;
use app\common\model\PhoneModel;

class ConsoleController extends BaseController
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function tableData()
    {
        $redis = new RedisController('sync');
        $warehouse_model = new WarehouseModel();
        $ware_model_result = $warehouse_model->countNumber();
        $count = count($ware_model_result);
        $data = [];
        $success_count = 0;
        $failed_count = 0;
        for ($i = 0; $i < $count; $i++){
            $title = $ware_model_result[$i]['title'];
            $data[$i]['url'] = $ware_model_result[$i]['url'];
            $data[$i]['warehouse'] = $title;
            $data[$i]['success_number'] = $redis->redisCheck('success_' . $title);
            $data[$i]['failed_number'] = $redis->redisCheck('failed_' . $title);
            $success_count = $data[$i]['success_number'] + $success_count;
            $failed_count = $data[$i]['failed_number'] + $failed_count;
        }
        $url = $redis->redisSetStringValue('curl_url');
        $proxy = json_decode(file_get_contents('http://'.$url.'/proxy'), true);
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'proxy_count' => $proxy['count'],
            'proxy_url' => $proxy['url'],
            'phone_count' => (new PhoneModel())->getPhoneCount(0),
            'data' => $data,
        ];
        return json($result);
    }

    //整合layui数据表格式
    public function tableData1()
    {
        $data = input('get.');
        if (empty($data['data'])) {
            $time = 'yesterday';
        } else {
            $time = $data['data']['time'];
        }
        $redis = new RedisController();
        $click_model = new ClickModel();
        $redis_click_ranking = $redis->redisCheck('click_ranking_' . $time);
        if ($redis_click_ranking) {
            $result = json_decode($redis_click_ranking);
        } else {
            $result = $click_model->searchClickPhone($time);
            $result = $this->phoneClickCount($result, $time);
            $result = [
                'code' => 0,
                'msg' => '',
                'count' => count($result),
                'time_count' => $click_model->clickCount($time),
                'data' => $result,
            ];
            $redis->redisSetCache('click_ranking_' . $time, json_encode($result, true), 86400);
        }
        return json($result);
    }

    //清除缓存
    public function clearCache()
    {
        $result = (new RedisController())->delPrefixRedis('click_ranking');
        if ($result) {
            return show('清除成功');
        } else {
            return show('清除失败', '', 4000);
        }
    }

    //根据查询到的手机号码,遍历查询每个的总数
    public function phoneClickCount($result, $time)
    {
        $click_model = new ClickModel();
        for ($i = 0; $i < count($result); $i++) {
            $click_num = $click_model->searchClickNumber($result[$i]['phone_id'], $time);
            $result[$i]['click_num'] = $click_num;
        }
        return $result;
    }
    
    //设置当前代理
    public function setProxyUrlNumber(){
        $redis = new RedisController();
        $data['proxy_url_number'] = input('post.proxy_url_number');
        $curl_url = 'http://' . $redis->redisCheck('curl_url') . '/setproxy';
        $result = curl_post($curl_url, $data);
        if ($result == $data['proxy_url_number']){
            return show('更改代理成功', $result);
        }else{
            return show('更改代理失败', '', 4000);
        }
    }
}
