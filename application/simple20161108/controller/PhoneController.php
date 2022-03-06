<?php

namespace app\simple20161108\controller;

use app\simple20161108\validate\PhoneValidate;
use app\common\controller\RedisController;
use app\common\model\CountryModel;
use app\common\model\PhoneModel;
use app\common\model\WarehouseModel;
use app\api\controller\ApiController;
use think\facade\Config;
use think\facade\Request;
use think\Db;

class PhoneController extends BaseController
{

    public function index()
    {
        //查询所有仓库信息用于下拉查询
        $warehouse = (new WarehouseModel())->allData();
        //获取国家列表
        $country = (new CountryModel())->allDataName();
        //dump($country);
        $this->assign('warehouse', $warehouse);
        $this->assign('country', $country);
        return $this->fetch();
    }

    //整合layui数据表格式
    public function tableData()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $phone_model = new PhoneModel();
        if (array_key_exists('reset', $data)) {
            $result = $phone_model->adminGetPhone(trim($data['data']['phone_num']));
            $count = count($result);
        } elseif (array_key_exists('warehouse', $data)) {
            $result = $phone_model->searchWarehouse(trim($data['data']['warehouse_id']), $page, $limit);
            $count = $phone_model->warehouseCount(trim($data['data']['warehouse_id']));
        }elseif(array_key_exists('country', $data)){
            $result = $phone_model->searchCountry(trim($data['data']['country_id']), $page, $limit);
            $count = $phone_model->countryCount(trim($data['data']['country_id']));
        } elseif(array_key_exists('show_all', $data)){
            $result = $phone_model->adminGetAllPhone($page, $limit);
            $count = (new PhoneModel())->getPhoneCount(4);
        } elseif(array_key_exists('report_sort', $data)){
            
        } else {
            $result = $phone_model->adminGetNormalPhone($page, $limit);
            $count = (new PhoneModel())->getPhoneCount(1);
        }
        $redis = new RedisController();
        for ($i = 0; $i < count($result); $i++) {
        	$result[$i]['report'] = $redis->redisCheck('report_' . $result[$i]['phone_num']);
            $result[$i]['country1'] = $result[$i]['country']['title'];
            $result[$i]['en_title'] = strtolower($result[$i]['country']['en_title']);
            unset($result[$i]['country']);
            $result[$i]['warehouse1'] = $result[$i]['warehouse']['url'];
            unset($result[$i]['warehouse']);
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }

    public function add()
    {
        //获取所有仓库和国家
        $get_country = (new CountryModel())->allData();
        $get_warehouse = (new WarehouseModel())->allData();
        $this->assign('country', $get_country);
        $this->assign('warehouse', $get_warehouse);
        return $this->fetch('add');
    }

    public function createPhone()
    {
        if (!Request::isPost()) {
            return show('非法请求', '', 5000);
        }
        $data = input('post.');
        $phone_model = new PhoneModel();
        $value = $phone_model->getPhoneValue($data['phone_num'], 'id');
        if ($value) {
            return show($data['phone_num'] . '已经存在', '', 3000);
        }
        $validate = new PhoneValidate();
        if (!$validate->check($data)) {
            return show($validate->getError(), '', 3000);
        }
        //拆分数据
        $data_bh = explode('|',$data['country_id']);
        $data['country_id'] = $data_bh[0];
        //写入数据
        $data['en_sort'] = $data['sort'];
        try {
            $data['uid'] = getRandNum(10);
            $result = $phone_model->createPhone($data);
        } catch (\Exception $e) {
            return show('添加出现异常', '', 4000);
        }
        /*$data['uid'] = getRandNum(10);
        phoneImage($data['country_id'], $data['phone_num'], $data['uid']);
        $result = $phone_model->createPhone($data);*/
        if ($result) {
            return show('添加成功');
        } else {
            return show('添加失败,请重试', '', 4000);
        }
    }
    
    //更新所有号码缓存
    public function update(){
        $phone_num = trim(input('post.phone_num'));
        $redis = new RedisController('master');
        $phone_model = new PhoneModel();
        
        $result = $phone_model->setPhoneCache($phone_num);
        
        if($result){
            return show('缓存更新成功'); 
        }else{
            return show('缓存更新失败,请重试', '', 4000);
        }
    }

    //开关切换
    public function check01()
    {
        $data = input('post.');
        $phone_num = $data['phone_num'];
        if ($data['field'] == 'sort' || $data['field'] == 'en_sort' || $data['field'] == 'phone_id' || $data['field'] == 'type') {
            $value = $data['value'];
        } else {
            if ($data['value'] == 0) {
                $value = 1;
            } elseif ($data['value'] == 1) {
                $value = 0;
            }
        }
        $phone_model = new PhoneModel();
        switch ($data['field']) {
            case 'online':
                $result = $phone_model->check01($phone_num, 'online', $value);
                break;
            case 'show':
                $result = $phone_model->check01($phone_num, 'show', $value);
                break;
            case 'sort':
                $result = $phone_model->check01($phone_num, 'sort', $value);
                break;
            case 'en_sort':
                $result = $phone_model->check01($phone_num, 'en_sort', $value);
                break;    
            case 'phone_id':
                $result = $phone_model->check01($phone_num, 'phone_id', $value);
                break;
            case 'display':
                $result = $phone_model->check01($phone_num, 'display', $value);
                break;
            case 'type':
                $result = $phone_model->check01($phone_num, 'type', $value);
                break;
            default:
                $result = '';
        }
        
        if (!$result) {
            return show('切换失败,请稍候重试', '', 4000);
        } else {
            $result = $phone_model->setPhoneCache($phone_num);
            if($result){
                return show('信息更改成功，已更新缓存', $result);
            }else{
                return show('信息更改成功，缓存更新失败', $result);
            }
        }
    }

    public function deleteMany()
    {
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new PhoneModel())->deleteMany($id);
        if (!$result) {
            return show('删除失败,请稍候重试', '', 4000);
        } else {
            $redis = new RedisController();
            $redis->delRedis();
            return show('删除成功', $result);
        }
    }

    //根据选择仓库搜索对应的内容 重载表格
    public function searchWarehouse()
    {
        $warehouse = input('param.id');
        $result = (new PhoneModel())->searchWarehouse($warehouse);
        if (!$result) {
            return show('查询失败,请稍候重试', '', 4000);
        } else {
            return show('查询成功', $result);
        }
    }

    //后台批量显示 隐藏 在线离线
    public function batchCheck()
    {
        $data = input('post.');
        $update = [];
        if ($data['value'] == 0){
            $value = 1;
        }else{
            $value = 0;
        }
        if ($data['type'] == 'online') {
            for ($i = 0; $i < count($data['data']); $i++) {
                $update[$i]['id'] = $data['data'][$i]['id'];
                $update[$i]['online'] = $value;
            }
        }else{
            for ($i = 0; $i < count($data['data']); $i++) {
                $update[$i]['id'] = $data['data'][$i]['id'];
                $update[$i]['show'] = $value;
            }
        }
        $result = (new PhoneModel())->batchCheck($update);
        if (!$result) {
            return show('批量操作失败,请稍候重试', '', 4000);
        } else {
            $redis = new RedisController();
            $redis->delRedis();
            return show('批量操作成功', $result);
        }
    }
    
    //后台显示采集信息
    public function adminShowMsg(){
        $phone_num = Request::param('phone_num');
        $phone_model = new PhoneModel();
        $result = $phone_model->getPhoneNum($phone_num);
        $result_sms = (new ApiController())->adminGetSMS($phone_num);
        $result_sms = json_decode($result_sms->getContent(), true);
        if($result_sms['error_code'] != 0){
            return $this->error($result_sms['msg']);
        }
        $result_sms = $result_sms['data'];
        $this->assign('data', $result_sms);
        $this->assign('phone_num', $phone_num);
        $this->assign('bh', $result['country']['bh']);
        $this->assign('country', $result['country']['title']);
        return $this->fetch('message');
    }
    
    //后台清除message页用户反馈失效信息
    public function removeReport(){
        $phone_num = input('post.phone_num');
        $redis = new RedisController();
        $result = $redis->deleteString('report_' . $phone_num);
        if (!$result){
            return show('清除redis反馈数据失败', '', 4000);
        }
        return show('清除成功', $result);
    }
}
