<?php


namespace app\common\controller;


use think\Controller;

class RelUrlController extends Controller
{
    private $url = 'https://mytempsms.com/receive-sms-online/';
    
    function relUrl($module, $phone_num = null, $country = null, $page = null){
        //return 'module:'.$module . '  phone_num:'.$phone_num . '  country:'.$country . '  page:'.$page;
        switch ($module){
            case 'Country':
                return $this->url . 'country.html';
            case 'Mail':
                return 'https://mytempsms.com/mail.html';
            case 'Message':
                if ($page){
                    //https://mytempsms.com/receive-sms-online/china-phone-number-16532819721/2.html
                    return $this->url . $country . '-phone-number-' . $phone_num . '/' . $page . '.html';
                }else {
                    //https://mytempsms.com/receive-sms-online/china-phone-number-16532819721.html
                    return $this->url . $country . '-phone-number-' . $phone_num . '.html';
                }
                break;
            case 'Phone':
                if (!$country && !$page){
                    return 'https://mytempsms.com/';
                }
                //https://mytempsms.com/receive-sms-online/phone-number/page/2.html
                if (!$country && $page){
                    return $this->url . 'phone-number/page/' . $page . '.html';
                }
                //https://mytempsms.com/receive-sms-online/china-phone-number.html
                if ($country && !$page){
                    return $this->url . $country . '-phone-number' . '.html';
                }
                //https://mytempsms.com/receive-sms-online/china-phone-number/page/4.html
                if ($country && $page){
                    return $this->url . $country . '-phone-number/page/' . $page . '.html';
                }
            case 'Article':
                if ($phone_num){
                    return $this->url . 'blog/' . $phone_num . '.html';
                }else{
                    return $this->url . 'blog.html';
                }    
            case 'Project':
                return 'https://mytempsms.com/receive-sms-from/' . $phone_num;    

        }
    }
}