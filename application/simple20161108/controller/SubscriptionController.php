<?php


namespace app\simple20161108\controller;

use think\facade\Request;
use app\common\model\MailboxModel;
use app\common\controller\MailController;
use bt\BtEmailServer;
use think\facade\Log;
use Ip2Region;

class SubscriptionController extends BaseController
{
    public function index(){
        $dates = $this->batchForDate();
        $this->assign('dates', $dates);
        return $this->fetch();
    }
    
    public function sendMail(){
        if (Request::isPost()){
            $data['success'] = 0;
            $data['failed'] = 0;
            $subject = input('post.subject');
            $body = htmlspecialchars_decode(input('post.body'));
            $mails = input('post.mails');
            $mails = explode(',', $mails);
            if (empty($mails[0])){
                //获取所有订阅邮件
                $mailboxs = (new MailboxModel())->getALLMail();
            }else{
                $mailboxs = $mails;
            }
            for ($i = 0; $i < count($mailboxs); $i++){
                $send_mail = $mailboxs[$i];
            	//Log::write($send_mail,'notice');
                $send = (new BtEmailServer())->sendEmail('service@*.com',$send_mail, $subject, $body);
                if ($send['status']){
                    $data['success']++;
                    Log::write($i+1 .'：发送成功_' . $send_mail . ' 已完成'. round((($i+1)/count($mailboxs))*100) . '%','notice');
                }else{
                    $data['failed']++;
                    Log::write($i+1 .'：发送失败_' . $send_mail . ' 已完成'. round((($i+1)/count($mailboxs))*100) . '%','notice');
                }
            }
            return show('发送成功：' . $data['success'] . '  发送失败：' . $data['failed'], $send);
        }else{
            return $this->fetch('send');
        }
    }
    
    public function adminMail(){
        return $this->fetch('admin');
    }

    public function adminMailData(){
        $bt = new BtEmailServer();
        $mail = $bt->getEmail('service@*.com');
        $data = $mail['data'];
        foreach ($data as $key => $value){
        	$data[$key]['time'] = date('Y-m-d H:m:s', $value['time']);
        	preg_match("/\<(.*)\>/", $data[$key]['from'], $matches);
        	$data[$key]['from'] = $matches[1];
        }
        
        if ($mail){
            $result = [
                'code' => 0,
                'msg' => '',
                'count' => count($mail['data']),
                'data' => $data,
            ];
            return json($result);
        }
    }

    public function batchForDate(){

        $mailbox_model = new MailboxModel();
        $dates = [];
        $year = date('Y', time());
        $month = date('m', time());
        $today = date('d', time());
        //计算上个月的最后一天 $last_day
        $time =  strtotime(date('Y-m-01'));
        $last_day = date('d',strtotime('-1 day',$time));
        for ($i = 0; $i < 12; $i++){
            //日份处理
            if ($today > 0){
                if ($i > 0){
                    $today--;
                }
                if ($today == 0){
                    $today = $last_day - $today;
                    $today_month = date('m', time()) - 1;
                }
            }
            if ($month > 1){
                if ($i > 0){
                    $month--;
                }
            }else{
                $month = 13 - $month;
                $month_year = $year - 1;
            }
            //$dates['month'][$i] = $mailbox_model->getDateData($today);
            $today_month = isset($today_month) ? $today_month : $month;
            //echo $today_month;
            $month_year = isset($month_year) ? $month_year : $year;

            $day_date = $year . '-' . $today_month . '-' . $today;
            $month_date = [$month_year . '-' . $month . '-' . 1, $month_year . '-' . $month . '-' . 31];
            $dates['date'][$i] = substr($month_year, -2) . '.'.$month . ' / '.$today_month.'.' . $today;
            $dates['today'][$i] = $mailbox_model->getBetweenTime($day_date);
            $dates['month'][$i] = $mailbox_model->getBetweenTime($month_date);
        }
        $dates['date'] = array_reverse($dates['date']);
        $dates['today'] = array_reverse($dates['today']);
        $dates['month'] = array_reverse($dates['month']);
        return $dates;
    }

    public function tableData(){
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $mailbox_model = new MailboxModel();
        $result = $mailbox_model->adminList($page, $limit);
        //解析IP地址
        $ip2region = new Ip2Region();
        foreach ($result as $key=>$value){
            $ip_info = $ip2region->memorySearch($value['ip'])['region'];
            $ip = getIpRegion($ip_info);
            $result[$key]['ip'] = $ip . '('.$value['ip'] . ')';
        }
        $count = $mailbox_model->getCount();
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
        $result = (new MailboxModel())->deleteMany($id);
        if (!$result){
            return show('删除失败,请稍候重试', '', 4000);
        }else{
            return show('删除成功');
        }
    }
}