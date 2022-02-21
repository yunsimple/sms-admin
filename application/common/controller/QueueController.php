<?php
namespace app\common\controller;

use app\common\model\CollectionMsgModel;
use app\common\model\PhoneModel;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Log;
use app\common\model\WarehouseModel;
use think\Exception;

class QueueController extends Controller
{
    //msg采集到后添加队列处理
    public function msgSaveDbQueue()
    {
        //查询列表内是否有数据
        $redis = new RedisController('sync');
        if(Config::get('database.subdomain') == 'best20161108'){
            $key = Config::get('cache.prefix') . 'message:';
        }else{
            $key = '';
        }
        $value = $redis->getSetAllValue($key . 'msg_queue');
        if ($value) {
            //随机取出一个号码,留20条数据,其他的全部放入数据库
            $number = 0;
            for ($i = 0; $i < count($value); $i++) {
                $data = $redis->getZsetScore($key . $value[$i]);
                $data_number = count($data);
                if ($data_number > 0) {
                    //echo $value[$i] . '----提取成功' . $data_number . '条----';
                    //把提取出来的数据存入数据库
                    $batch_data = $this->msgBatchData($value[$i], $data);
                    //dump($batch_data);
                    try {
                        //$create_data = (new CollectionMsgModel())->batchCreate($batch_data);
                        $create_data = Db::table('collection_msg')->insertAll($batch_data);
                    }catch (Exception $e){
                        $redis->deleteZset($key . $value[$i]);
                        continue;
                    };
                    //$create_number = count($create_data);
                    if ($create_data > 0) {
                        //echo '----写入数据库成功' . $create_number . '条';
                        $number = $number + $create_data;
                        //数据写入本地备用数据库
                        /*$local_result = (new CollectionMsgModel())->batchCreate($batch_data, 'local');
                        if (count($local_result) <= 0){
                        	echo '----写入本地备用数据库失败';
                        }*/
                        //删除提取出来的数据
                        $delete_number = $redis->deleteZset($key . $value[$i]);
                        if ($delete_number > 0) {
                            //echo '----删除成功' . $delete_number . '条';
                            //把当前号码移出集合
                            $delete_set_phone = $redis->deleteSet($key . 'msg_queue', $value[$i]);
                            if ($delete_set_phone > 0) {
                                //echo '----号码已经移出队列----' .date('Y-m-d H:i:s').'<br>';
                            } else {
                                echo $value[$i] . '----号码未能成功移出队列----' .date('Y-m-d H:i:s').'<br>';
                            }
                        } else {
                            echo '----删除失败';
                        }
                    } else {
                        echo '----写入数据库失败';
                    }
                } else {
                    //echo $value[$i] . '----提取失败';
                }
            }
            echo '----本次写入数据：' . $number . ' 条';
        } else {
            echo '并没有需要入库的数据';
        }
    }

    //msg采集到后添加队列处理
    public function clear()
    {
        //查询列表内是否有数据
        $redis = new RedisController('sync');
        if(Config::get('database.subdomain') == 'best20161108'){
            $key = Config::get('cache.prefix') . 'message:';
        }else{
            $key = 'msg_queue';
        }
        $value = $redis->getSetAllValue($key . 'msg_queue');
        if ($value) {
            //随机取出一个号码,留20条数据,其他的全部放入数据库
            $number = 0;
            for ($i = 0; $i < count($value); $i++) {
                $data = $redis->getZsetScore($value[$i]);
                $data_number = count($data);
                if ($data_number > 0) {
                    //echo $value[$i] . '----提取成功' . $data_number . '条----';
                    //把提取出来的数据存入数据库
                    $batch_data = $this->msgBatchData($value[$i], $data);
                    $create_data = 1;//(new CollectionMsgModel())->batchCreate($batch_data);
                    $create_number = 1;//count($create_data);
                    if ($create_number > 0) {
                        //echo '----写入数据库成功' . $create_number . '条';
                        $number = $number + $create_number;
                        //数据写入本地备用数据库
                        /*$local_result = (new CollectionMsgModel())->batchCreate($batch_data, 'local');
                        if (count($local_result) <= 0){
                        	echo '----写入本地备用数据库失败';
                        }*/
                        //删除提取出来的数据
                        $delete_number = $redis->deleteZset($value[$i]);
                        if ($delete_number > 0) {
                            //echo '----删除成功' . $delete_number . '条';
                            //把当前号码移出集合
                            $delete_set_phone = $redis->deleteSet('msg_queue', $value[$i]);
                            if ($delete_set_phone > 0) {
                                //echo '----号码已经移出队列----' .date('Y-m-d H:i:s').'<br>';
                            } else {
                                echo $value[$i] . '----号码未能成功移出队列----' .date('Y-m-d H:i:s').'<br>';
                            }
                        } else {
                            echo '----删除失败';
                        }
                    } else {
                        echo '----写入数据库失败';
                    }
                } else {
                    echo $value[$i] . '----提取失败';
                }
            }
            echo '----今日累计写入数据：' . $number . ' 条';
        } else {
            echo '并没有需要入库的数据';
        }
    }

    /**
     * 准备短信批量入库数据
     * arr['phone_id':111,'content':'string']
     */
    private function msgBatchData($phone_num, $data)
    {
        //获取phone_id
        //dump($data);
        if(Config::get('database.subdomain') == 'best20161108'){
            $phone_id = (new PhoneModel())::where('uid', '=', $phone_num)->value('id');
        }else{
            $phone_id = (new PhoneModel())::where('phone_num', '=', $phone_num)->value('id');
        }

        $arr = [];
        $number = count($data);
        for ($i = 0; $i < $number; $i++) {
            $da = unserialize($data[$i]);
            //dump($da);
            if (array_key_exists('url', $da)){
                $arr[$i]['url'] = $da['url'];
            }
            $arr[$i]['phone_id'] = $phone_id;
            $arr[$i]['smsContent'] = trim($da['smsContent']);
            $arr[$i]['smsNumber'] = trim($da['smsNumber']);
            $arr[$i]['smsDate'] = trim($da['smsDate']);
        }
        //halt($arr);
        return $arr;
    }

    //保存前一天的采集记录json保存到数据库
    public function saveYesterday(){
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
        return json_encode($result);
    }


}