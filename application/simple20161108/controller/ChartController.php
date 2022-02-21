<?php


namespace app\simple20161108\controller;


use app\common\model\HistoryModel;

class ChartController extends BaseController
{
    //代理采集统计
    public function proxyHistoryChart(){
        $result = (new HistoryModel())->searchTime(15);
        $data = [];
        for ($i = 0; $i < count($result); $i++){
            $data[$i] = json_decode($result[$i]['value_json'], true);
            $data[$i]['time'] = date('m-d', strtotime($result[$i]['create_time']));
        }
        //dump($data);
        $this->assign('history', $data);
        return $this->fetch('proxy_history');
    }
}