<?php
namespace app\simple20161108\controller;

use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use app\common\model\ActivityModel;
use Ip2Region;
use app\common\model\PhoneModel;
use app\common\controller\RedisController;


class AppController extends BaseController
{
    public function firebaseUser(){
        return $this->fetch('app/firebase_user');
    }

    public function firebaseUserTableData(): \think\response\Json
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $firebase_user_model = new FirebaseUserModel();
        if(array_key_exists('search', $data)){
            $title = $data['data']['title'];
            $startDate = $data['data']['startDate'];
            $endDate = $data['data']['endDate'];
            
            if($title){
                if($startDate && $endDate){
                    $result = $firebase_user_model
                        ->whereOr([['user', '=', $title], ['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $firebase_user_model
                        ->whereOr([['user', '=', $title], ['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->select();
                }
            }else{
                if(!$startDate && !$endDate){
                    $result = $firebase_user_model->page($page,$limit)->order('id', 'desc')->select();
                }elseif($startDate && $endDate){
                    $result = $firebase_user_model
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $firebase_user_model
                        ->order('id', 'desc')
                        ->select();
                }
            }
            
            $count = count($result);
        }else{
            $result = $firebase_user_model->page($page,$limit)->order('id', 'desc')->select();
            $count = $firebase_user_model->count();
        }
        //解析IP地址
        $ip2region = new Ip2Region();
        foreach ($result as $key=>$value){
            if($value['ip']){
                $ip_info = $ip2region->memorySearch($value['ip'])['region'];
                $ip = getIpRegion($ip_info);
                $result[$key]['ip'] = $ip . '('.$value['ip'] . ')';
            }
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }

    public function deleteFirebaseUserMany()
    {
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new FirebaseUserModel())->destroy($id);
        if (!$result) {
            return show('删除失败,请稍候重试', '', 4000);
        } else {
            return show('删除成功', $result);
        }
    }

    public function updateFirebaseUserField(){
        $data = input('post.');
        $id = $data['id'];
        $value = $data['value'];
        $field = $data['field'];
        $result = (new FirebaseUserModel())->where('id', $id)->update([$field => $value]);
        if (!$result) {
            return show('更新失败', '', 4000);
        } else {
            return show('更新成功', $result);
        }
    }

    public function adOrder(){
        return $this->fetch('app/ad_order');
    }

    public function adOrderTableData(): \think\response\Json
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $ad_order_model = new AdOrderModel();
        if (array_key_exists('search', $data)){
            //$result = $ad_order_model->whereOr([['user_id','like', '%'. $data['data']['title'] .'%'], ['phone_num','like', '%'. $data['data']['title'] .'%']])->order('id', 'desc')->select();
            
            $title = $data['data']['title'];
            $startDate = $data['data']['startDate'];
            $endDate = $data['data']['endDate'];
            
            if($title){
                if($startDate && $endDate){
                    $result = $ad_order_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $ad_order_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->select();
                }
            }else{
                if(!$startDate && !$endDate){
                    $result = $ad_order_model->page($page,$limit)->order('id', 'desc')->select();
                }elseif($startDate && $endDate){
                    $result = $ad_order_model
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $ad_order_model
                        ->order('id', 'desc')
                        ->select();
                }
            }
            
            $count = count($result);
        }else{
            $result = $ad_order_model->page($page,$limit)->order('id', 'desc')->select();
            $count = $ad_order_model->count();
        }
        //解析IP地址
        $ip2region = new Ip2Region();
        foreach ($result as $key=>$value){
            if($value['ip']){
                $ip_info = $ip2region->memorySearch($value['ip'])['region'];
                $ip = getIpRegion($ip_info);
                $result[$key]['ip'] = $ip . '('.$value['ip'] . ')';
            }
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }

    public function deleteAdOrderMany()
    {
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new AdOrderModel())->destroy($id);
        if (!$result) {
            return show('删除失败,请稍候重试', '', 4000);
        } else {
            return show('删除成功', $result);
        }
    }
    
    public function signIn(){
        return $this->fetch('app/sign_in');
    }

    public function signInTableData(): \think\response\Json
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $ad_order_model = new ActivityModel();
        if (array_key_exists('search', $data)){
            //$result = $ad_order_model->whereOr([['user_id','like', '%'. $data['data']['title'] .'%'], ['phone_num','like', '%'. $data['data']['title'] .'%']])->order('id', 'desc')->select();
            
            $title = $data['data']['title'];
            $startDate = $data['data']['startDate'];
            $endDate = $data['data']['endDate'];
            
            if($title){
                if($startDate && $endDate){
                    $result = $ad_order_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $ad_order_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->select();
                }
            }else{
                if(!$startDate && !$endDate){
                    $result = $ad_order_model->page($page,$limit)->order('id', 'desc')->select();
                }elseif($startDate && $endDate){
                    $result = $ad_order_model
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $ad_order_model
                        ->order('id', 'desc')
                        ->select();
                }
            }
            
            $count = count($result);
        }else{
            $result = $ad_order_model->page($page,$limit)->order('id', 'desc')->select();
            $count = $ad_order_model->count();
        }
        //解析IP地址
        $ip2region = new Ip2Region();
        foreach ($result as $key=>$value){
            if($value['ip']){
                $ip_info = $ip2region->memorySearch($value['ip'])['region'];
                $ip = getIpRegion($ip_info);
                $result[$key]['ip'] = $ip . '('.$value['ip'] . ')';
            }
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }

    public function deleteSignInMany()
    {
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new ActivityModel())->destroy($id);
        if (!$result) {
            return show('删除失败,请稍候重试', '', 4000);
        } else {
            return show('删除成功', $result);
        }
    }
    
    public function vip(){
        return $this->fetch('app/vip');
    }

    public function vipTableData(): \think\response\Json
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $phone_model = new PhoneModel();
        if (array_key_exists('search', $data)){
            //$result = $phone_model->whereOr([['user_id','like', '%'. $data['data']['title'] .'%'], ['phone_num','like', '%'. $data['data']['title'] .'%']])->order('id', 'desc')->select();
            
            $title = $data['data']['title'];
            $startDate = $data['data']['startDate'];
            $endDate = $data['data']['endDate'];
            
            if($title){
                if($startDate && $endDate){
                    $result = $phone_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $phone_model
                        ->whereOr([['user_id', '=', $title], ['ip', '=', $title]])
                        ->order('id', 'desc')
                        ->select();
                }
            }else{
                if(!$startDate && !$endDate){
                    $result = $phone_model->page($page,$limit)->order('id', 'desc')->select();
                }elseif($startDate && $endDate){
                    $result = $phone_model
                        ->order('id', 'desc')
                        ->whereTime('create_time', [$startDate, $endDate])
                        ->select();
                }else{
                    $result = $phone_model
                        ->order('id', 'desc')
                        ->select();
                }
            }
            
            $count = count($result);
        }else{
            $result = $phone_model->where('type', '=', 3)->page($page,$limit)->order('id', 'desc')->select();
            $count = $phone_model->where('type', '=', 3)->count();
        }
        
        $redis = new RedisController('master');
        $ad_order_model = new AdOrderModel();
        for ($i = 0; $i < count($result); $i++) {
            // 反馈数量
        	$result[$i]['report'] = $redis->redisCheck('report:' . $result[$i]['phone_num']);
            $result[$i]['country1'] = $result[$i]['country']['title'];
            $result[$i]['en_title'] = strtolower($result[$i]['country']['en_title']);
            unset($result[$i]['country']);
            $result[$i]['warehouse1'] = $result[$i]['warehouse']['url'];
            unset($result[$i]['warehouse']);
            
            // 查询vip号码被换购次数
            $result[$i]['buy_number'] = $ad_order_model->where('phone_num', '=', $result[$i]['phone_num'])->count();
        }
        
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }
    
}