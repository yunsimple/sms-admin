<?php

namespace app\common\controller;
//use mail\PHPMailer;
use think\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailController extends Controller
{
    public function sendMail($phone_num, $warehouse){
        $mail = new PHPMailer();
        $mail->IsSMTP(); // 使用SMTP方式发送
        $mail->CharSet='UTF-8';// 设置邮件的字符编码
        $mail->Host = 'smtp.163.com'; // 您的企业邮局服务器
        $mail->Port = 25; // 设置端口
        $mail->SMTPAuth = true; // 启用SMTP验证功能
        $mail->Username = "*@163.com"; // 邮局用户名(请填写完整的email地址)
        $mail->Password = "*"; // 邮局密码
        $mail->From = "*@163.com"; //邮件发送者email地址
        $mail->FromName = "系统提醒"; //发件人姓名
        $mail->AddAddress("*@163.com", "深蓝");//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
        $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
        $mail->Subject =$warehouse.'-' . $phone_num . " 下线通知";//"PHPMailer测试邮件"; //邮件标题
        $mail->Body = $phone_num . " 号码采集多次失败，请火速上线处理。"; //邮件内容
        if(!$mail->Send()){
        	return "错误原因: " . $mail->ErrorInfo;
        }else {
        	return true;
        }
    }
    
    public function noticeMail($content){
        $mail = new PHPMailer();
        $mail->IsSMTP(); // 使用SMTP方式发送
        $mail->CharSet='UTF-8';// 设置邮件的字符编码
        $mail->Host = 'smtp.163.com'; // 您的企业邮局服务器
        $mail->Port = 25; // 设置端口
        $mail->SMTPAuth = true; // 启用SMTP验证功能
        $mail->Username = "*@163.com"; // 邮局用户名(请填写完整的email地址)
        $mail->Password = "*"; // 邮局密码
        $mail->From = "*@163.com"; //邮件发送者email地址
        $mail->FromName = "系统提醒"; //发件人姓名
        $mail->AddAddress("*@163.com", "系统");//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
        $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
        $mail->Subject = $content;//"PHPMailer测试邮件"; //邮件标题
        $mail->Body = $content; //邮件内容
        if(!$mail->Send()){
        	return "错误原因: " . $mail->ErrorInfo;
        }else {
        	return true;
        }
    }
    
    public function sendMailSubscriptions($send_mail, $subject, $body){
        //return 'success';
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->CharSet='UTF-8';// 设置邮件的字符编码
        $mail->Username = "*@gmail.com"; // 邮局用户名(请填写完整的email地址)
        $mail->Password = "*"; // 邮局密码
        $mail->From = "*@gmail.com"; //邮件发送者email地址
        $mail->FromName = "*"; //发件人姓名
        $mail->AddAddress($send_mail, "用户");
        $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
        $mail->Subject = $subject;
        $mail->Body = $body;
        if (!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return 'success';
        }
    }
    
    public function sendMailLocal($send_mail, $subject, $body){
        $mail = new PHPMailer;
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = 'mail.mailscode.com';
        $mail->Port = 25;
        $mail->SMTPSecure = "ssl";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->CharSet='UTF-8';// 设置邮件的字符编码
        $mail->Username = ""; // 邮局用户名(请填写完整的email地址)
        $mail->Password = ""; // 邮局密码
        $mail->From = ""; //邮件发送者email地址
        //$mail->addReplyTo('admin@mailscode.com', '隐私短信');
        $mail->FromName = ""; //发件人姓名
        $mail->AddAddress($send_mail, "用户");
        //$mail->SingleTo = true; //将邮件分发到每个电子邮件地址
        $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
        $mail->Subject = $subject;
        $mail->Body = $body;
        //$mail->clearAddresses();//清除收件人，为下一次发件做准备
		//$mail->ClearAllRecipients();//清除所有收件人，包括CC(抄送)和BCC(密送)
        if (!$mail->send()) {
            dump($mail->ErrorInfo);
            return 'failed';
        } else {
            return 'success';
        }
    }
}