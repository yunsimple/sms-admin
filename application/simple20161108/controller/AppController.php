<?php
namespace app\simple20161108\controller;

use app\common\model\AdOrderModel;
use app\common\model\FirebaseUserModel;
use Ip2Region;

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
            $result = $firebase_user_model->whereOr([['user','like', '%'. $data['data']['title'] .'%'], ['user_id','like', '%'. $data['data']['title'] .'%']])->order('id', 'desc')->select();
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
            $result = $ad_order_model->whereOr([['user_id','like', '%'. $data['data']['title'] .'%'], ['phone_num','like', '%'. $data['data']['title'] .'%']])->order('id', 'desc')->select();
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
}