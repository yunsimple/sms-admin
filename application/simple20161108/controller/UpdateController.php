<?php

namespace app\simple20161108\controller;

use app\common\controller\RedisController;
use app\api\controller\MailController;
use think\facade\Request;
use app\common\model\PhoneModel;
use think\Controller;

class UpdateController extends Controller
{
    //Becmd更新同步
    public function Update($website = null){
        //$url = (new RedisController())->redisSetStringValue('curl_url');
        //$url = 'http://'.$url.'/proxy_url_content?url=https://www.becmd.com';
        //通过代理采集
	    $html = curl_get('https://www.becmd.com');
	    //dump($html);die;
	    preg_match_all('/47px\"\>\+(.*?)\<\/h2/', $html, $number);
	    $number = $number[1];
	    if(count($number) < 10){
	        echo '采集失败';
	        return false;
	    }
	    return json_encode($number);
        $phone_model = new PhoneModel();
        $warehouse_phone = $phone_model->getWarehouseAll(3, 'phone_num');
        //dump($number_old);die;
	    for($i = 0; $i < count($number); $i++){
	        if (substr($number[$i], 0, 1) == 1){
                $number[$i] = substr($number[$i], 1);
                $country_id = 3;
            }else{
                $number[$i] = substr($number[$i], 2);
                $country_id = 1;
            }
            //判断数据库是否存在
            $phone = $phone_model->getPhoneValue($number[$i], 'id');
	        if (!$phone){
	            $data = [
	                'phone_num' => $number[$i],
                    'country_id' => $country_id,
                    'warehouse_id' => 3,
                    'online' => 1,
                    'show' => 1
                ];
                $create_result = $phone_model->createPhone($data);
                if ($create_result > 0){
                    echo $i+1 . '★' . $number[$i] . '新增成功';
                }
            }else{
                echo $i+1 . '⊙' . $number[$i] .'||';
            }
        }

        //比对新号码，判断是否下架 $number_old $number
        foreach ($warehouse_phone as $old){
            if (!in_array($old, $number)){
                $result = $phone_model->check01($old, 'online', 0);
                $phone_model->check01($old, 'sort', -9);
                if ($result){
                    echo '==' . $old . '号码重复，已成功下线';
                }
            }
        }
    }
}