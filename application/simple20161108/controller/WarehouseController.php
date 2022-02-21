<?php

namespace app\simple20161108\controller;

use app\common\model\WarehouseModel;
use think\facade\Request;
use think\facade\Validate;

class WarehouseController extends BaseController
{
    public function index()
    {
        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch('warehouse/add');
    }

    public function createWareHouse()
    {
        if (!Request::isPost()) {
            return show('非法请求', '', 5000);
        }
        $data = input('post.');
        $warehouse_model = new WarehouseModel();

        $value = $warehouse_model->search($data['url']);
        if ($value != 0) {
            return show($data['phone_num'] . '已经存在', '', 3000);
        }
        $validate = Validate::checkRule($data['url'], 'must|url');
        if (!$validate) {
            //return $this->error('url传递异常');
            return show('url传递异常', '', 3000);
        }
        //写入数据

        $result = $warehouse_model->createWareHouse($data);
        if ($result) {
            return show('添加成功', $result);
        } else {
            return show('添加失败,请重试', '', 4000);
        }
    }

    public function tableData()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $warehouse_model = new WarehouseModel();
        $result = $warehouse_model->allWarehouse($page, $limit);
        $count = $result->count();
        $result = [
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
        ];
        return json($result);
    }

    //开关切换
    public function check01()
    {
        $value = input('post.value');
        $id = input('post.warehouse_id');
        $field = input('post.field');
        if (empty($value)) {
            $value = 1;
        } elseif ($value == '1') {
            $value = 0;
        }
        $result = (new WarehouseModel())->check01($id, $value, $field);
        if (!$result) {
            return show('切换失败,请稍候重试', '', 4000);
        } else {
            return show('修改成功', $result);
        }
    }
}
