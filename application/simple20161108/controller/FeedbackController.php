<?php


namespace app\simple20161108\controller;


use app\common\model\FeedbackModel;
use Ip2Region;

class FeedbackController extends BaseController
{
    public function index(){
        return $this->fetch();
    }

    public function tableData(){
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $feedback_model = new FeedbackModel();
        $result = $feedback_model->adminList($page, $limit);
        //解析IP地址
        $ip2region = new Ip2Region();
        foreach ($result as $key=>$value){
            $ip_info = $ip2region->memorySearch($value['ip'])['region'];
            $ip = getIpRegion($ip_info);
            $result[$key]['ip'] = $ip . '('.$value['ip'] . ')';
        }
        $count = $feedback_model->getCount();
        if ($result){
            $result = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $result,
            ];
            return json($result);
        }
    }

    public function deleteMany(){
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new FeedbackModel())->deleteMany($id);
        if (!$result){
            return show('删除失败,请稍候重试', '', 4000);
        }else{
            return show('删除成功');
        }
    }
}