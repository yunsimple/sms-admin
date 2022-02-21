<?php


namespace app\simple20161108\controller;


use app\common\model\JiemaOrderModel;
use app\common\model\JiemaProjectModel;
use app\common\model\UserModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class JiemaController extends BaseController
{
    public function recharge(){
        if (!Request::isPost()){
            return $this->fetch('recharge');
        }
        $data = input('post.');
        $order_data = [];
        if ($data['recharge_type'] == 3){
            $order_data['message'] = $data['setdec_info'];
        }
        $money = $data['money'];
        switch ($data['recharge_type']){
            case 1:
                $order_data['message'] = !$data['setdec_info'] ? '充值金额：' . $money : $data['setdec_info'];
                break;
            case 2:
                $order_data['message'] = !$data['setdec_info'] ? '赠送金额：' . $money : $data['setdec_info'];
                break;
            case 3:
                $data['money'] = -$data['money'];
                $order_data['message'] = $data['setdec_info'];
                break;
            default:
                $order_data['message'] = '';
        }
        $order_data['order'] = $this->generateOrderNumber();
        $order_data['number'] = '';
        $order_data['project_id'] = $data['recharge_type'];
        $order_data['user'] = $data['user'];
        $order_data['money'] = $data['money'];
        $order_data['before_money'] = (new UserModel())->getFieldValue($data['user'], 'money');
        $order_data['create_time'] = time();
        $order_data['update_time'] = time();
        Db::transaction(function () use ($order_data){
            $order_data['money'] > 0 ? $set = 'setInc' : $set = 'setDec';
            //1.扣余额，2.写入流水
            Db::table('user')->where('name', $order_data['user'])->$set('money', abs($order_data['money']));
            Db::table('jiema_order')->insert($order_data);
        });
        return show('充值成功');
    }

    public function order(){
        return $this->fetch('order');
    }

    public function user(){
        return $this->fetch('user');
    }

    public function project(){
        if (!Request::isPost()){
            return $this->fetch('project');
        }
        $data = input('post.');
        $result = (new JiemaProjectModel())->createProject($data);
        if ($result){
            return show('添加成功');
        }else{
            return show('添加失败', '', 4000);
        }
    }

    //生成订单号
    protected function generateOrderNumber(){
        $order = 'CZ'. date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $order;
    }

    //订单流水
    public function jiemaOrder()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $user = Session::get('user_login');
        $jiema_order_model = new JiemaOrderModel();
        $data = $jiema_order_model->getAllList($page, $limit);
        $new_data = [];
        for ($i = 0; $i < count($data); $i++){
            $new_data[$i]['type'] = $data[$i]['jiemaproject']['type'];
            $new_data[$i]['order'] = $data[$i]['order'];
            $new_data[$i]['number'] = $data[$i]['number'];
            $new_data[$i]['user'] = $data[$i]['user'];
            $new_data[$i]['message'] = $data[$i]['message'];
            $new_data[$i]['before_money'] = $data[$i]['before_money'];
            $new_data[$i]['title'] = $data[$i]['jiemaproject']['title'];
            $new_data[$i]['money'] = $data[$i]['money'];
            $new_data[$i]['create_time'] = $data[$i]['create_time'];
        }
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => 1000,
            'data' => $new_data,
        ];
        return json($result);
    }

    //订单流水
    public function jiemaUser()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $jiema_user_model = new UserModel();
        $data = $jiema_user_model->getAllUser($page, $limit);
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => 1000,
            'data' => $data,
        ];
        return json($result);
    }

    //后台项目列表
    public function jiemaProject()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $data = (new JiemaProjectModel())->getAllProject($page, $limit);
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => 1000,
            'data' => $data,
        ];
        return json($result);
    }
}