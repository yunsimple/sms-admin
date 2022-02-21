<?php

namespace app\simple20161108\controller;


use app\common\model\FeedbackModel;
use app\common\model\MailboxModel;

class IndexController extends BaseController
{
    public function index(){
        $remind = $this->remind();
        $this->assign('remind', $remind);
        return $this->fetch();
    }

    //获取当天新增的订阅以及反馈
    public function remind(){
        $feedback_model = new FeedbackModel();
        $mailbox_model = new MailboxModel();
        $feedback_count = $feedback_model->getDateData('today');
        $mailbox_count = $mailbox_model->getDateData('today');
        $remind['feedback'] = $feedback_count;
        $remind['mailbox'] = $mailbox_count;
        return $remind;
    }
}