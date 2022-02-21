<?php
namespace app\simple20161108\controller;


use app\common\controller\QueueController;
use app\common\controller\RedisController;
use bt\Bt;
use bt\BtEmailServer;
use app\api\controller\MailController;
use app\simple20161108\controller\ConsoleController;
use app\common\model\HistoryModel;
use app\common\controller\SitemapController;
use app\common\controller\SitemapMytempsmsController;
use app\api\controller\ApiController;
use think\facade\Request;
use app\common\model\PhoneModel;
use think\Controller;
use think\Db;
use think\facade\Log;

class ServiceQueueController extends Controller
{
    public function index(){
        trace('service_queue','notice');
        return 'service_queue';
    }
    public function test(){
        return gap_times(1626747605,'en', 'ago');
        Log::write('任务数量：', 'notice');
    }
    
    public function zhuanyi(){
        $phone_model = new PhoneModel;
        $phone = $phone_model->adminGetAll(4);
        $total = 600;
        Log::write('任务数量：' . count($phone), 'notice');
        foreach ($phone as $key => $item){
            Log::write($item['phone_num'] .'开始写入', 'notice');
            $list = Db::connect('db_collection_nonlocal_config')->name('collection_msg')->where('phone_id', $item['id'])->order('id', 'desc')->page(1, 1000)->select();
            if (count($list) > 20){
                try {
                    foreach ($list as $k => $sms){
                        $sms['id'] = 0;
                        $val = Db::name('collection_msg')->insert($sms);
                        if (!$val){
                            Log::write($item['phone_num'] . '第' . $k .'条插入失败44444444444', 'notice');
                        }
                    }
                    Log::write(($total + $key) . '-' .$item['phone_num'] .'写入成功okokokok', 'notice');
                }catch (Exception $e){
                    Log::write($item['phone_num'] .'条插入失败**********', 'notice');
                    continue;
                };
            }else{
                Log::write('数量太少,跳过-------------', 'notice');
            }

        }
    }

    public function cc(){
        $total = 1100;
        for ($i = 1023; $i < $total; $i++){
            Log::write('第'.$i.'个十万写入开始...', 'notice');
            $list = Db::connect('db_collection_nonlocal_config')->name('collection_msg')->order('id', 'desc')->page($i,10000)->select();
            foreach ($list as $key => $value){
                //dump($list[$key]);
                $data = unserialize($this->mb_unserialize($value['content']));
                //dump($data);
                $data['PhoNum'] = $list[$key]['phone_id'];
                $list[$key]['content'] = serialize($data);
                $add = Db::connect('db_collection_nonlocal_config')->name('collection_msg')->where('id', $list[$key]['id'])->update($list[$key]);
                //dump($add);
            }
            Log::write('第'.$i.'个十万写入结束...', 'notice');
        }
    }
    
    public function bb(){
        $phone = (new PhoneModel)->adminGetAllPhone(1,2000);
        $redis = new RedisController('sync');
        for($i = 0; $i < count($phone); $i++){
            dump($phone[$i]['phone_num']);
            $count = Db::table('collection_msg')->where('phone_id', $phone[$i]['id'])->count();
            dump($count);
            $count = $redis->hSet('phone_receive', $phone[$i]['phone_num'], $count);
            echo $count . '-----------';
        }
        
    }

    //序列化处理
    public function mb_unserialize($str) {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s',function($match){return 's:'.strlen($match[2]).':"'.$match[2].'";';},$str);
    }
    
    //生成号码图片
    public function test2(){
        $data = Db::table('phone')->alias('p')->where('p.id', '<', 1576)->join('country c', 'p.country_id = c.id')->field('p.id, p.uid,p.phone_num,c.bh,c.title')->select();
        //halt($data);
        $redis = new RedisController('sync');
        foreach($data as $key => $value){
                //生成新号,redis里面的姓名也要改
                $rand = mt_rand(100000,999999);
                $new_phone = substr_replace($value['phone_num'], $rand, -6);
                trace($value['phone_num'] . "|" . $new_phone);
                echo '老号：' . $value['phone_num'] . '<br>';
                echo '新号：' . $new_phone . '<br>';
                
                $result = Db::table('phone')->where('id', $value['id'])->update(['phone_num' => $new_phone]);
                
                if($result){
                    echo '数据库更改成功<br>';
                    $image = phoneImage($value['bh'], $new_phone, $value['uid']);
                    
                    if($image){
                        echo '图片生成成功<br>';
                    }else{
                        echo '图片生成失败<br>';
                    }
                    
                    $redis_rename = $redis->rename($value['phone_num'], $new_phone);
                    $redis->rename('msg_'.$value['phone_num'].'_score', 'msg_'.$new_phone.'_score');
                    if($redis_rename){
                        echo 'redis改名成功<br>';
                    }else{
                        echo 'redis改名失败<br>';
                    }
                    
                    echo '<br><br>';

                }else{
                    echo $value['phone_num'] . '失败失败失败<br><br>';
                }
            
        }
    }
    
    //生成号码图片
    public function grentimage(){
        $data = Db::table('phone')->alias('p')->join('country c', 'p.country_id = c.id')->field('p.id, p.uid,p.phone_num,c.bh,c.title')->select();
        foreach($data as $key => $value){
            $image = phoneImage($value['bh'], $value['phone_num'], $value['uid']);
        }
    }
    
    //演示号码上架,上架后，还要清理redis号码信息
    public function newphone(){
        $redis = new RedisController();
        $data = Db::table('phone')->where('type', 2)->select();
        //halt($data);
        foreach($data as $value){
            Db::table('phone')->where('id', $value['id'])->update(['type', 1]);
            $redis->deleteString('phone_detail_' . $value['uid']);
        }
        $redis->delRedis();
        curl_get("http://106.55.24.14/?key=qywsxxl&content=新款上新成功");
    }
    
    
    //collection_msg转新库
    public function collection_new()
    {
        $phone = Db::name('phone')->where('id', '>', 1560)->select();
        $total = count($phone);
        //halt($total);
        Log::write('任务数量：' . $total, 'notice');
        foreach ($phone as $key => $item){
            Log::write($item['phone_num'] .'开始写入', 'notice');
            Log::write('剩余：' . ($total - $key), 'notice');
            $list = Db::connect('db_collection_nonlocal_config')->name('collection_msg')->where('phone_id', $item['id'])->order('id', 'desc')->page(1, 2000)->select();
            if (count($list) > 20){
                try {
                    foreach ($list as $k => $sms){
                        //dump($sms);
                        $content = unserialize($this->mb_unserialize($sms['content']));
                        $number = trim($content['smsNumber']);
                        if (strlen($number) > 15){
                            $number = substr($number, 0, 15);
                        }
                        $sms['sms_number'] = $number;
                        $sms['sms_content'] = trim($content['smsContent']);
                        $date = trim($content['smsDate']);
                        if (is_numeric($date)){
                            $sms['sms_date'] = $date;
                        }else{
                            $sms['sms_date'] = 1591928341;
                        }
                        unset($sms['content']);
                        unset($sms['id']);
                        //dump($sms);
                        $val = Db::name('collection_msg')->insert($sms);
                        if (!$val){
                            Log::write($item['phone_num'] . '第' . $k .'条插入失败44444444444', 'notice');
                        }
                    }
                    Log::write(($total + $key) . '-' .$item['phone_num'] .'写入成功' . count($list) . '条', 'notice');
                }catch (\Exception $e){
                    Log::write($item['phone_num'] .'条插入失败|' . $e, 'notice');
                    continue;
                };
            }else{
                Log::write('数量太少,跳过-------------', 'notice');
            }
        }
    }
    
    public function test1(){
        //生成随机号码写入
        $data = Db::table('phone')->select();
        dump($data);
        foreach($data as $key => $value){
            $uid = getRandNum(10);
            Db::table('phone')->where('id', $value['id'])->update(['uid' => $uid]);
        }
    }
    public function delredis(){
        $name = input('get.name');
        return (new RedisController())->delPrefixRedis($name);
    }
    public function msg_insert(){
        $result = (new QueueController())->msgSaveDbQueue();
        return $result;
    }

    public function clear(){
        $result = (new QueueController())->clear();
        return $result;
    }
    
    public function testSpeed(){
        debug('begin');
        dump(checkSpider('72.14.199.108'));
        // ...其他代码段
        debug('end');
        // ...也许这里还有其他代码
        // 进行统计区间
        echo debug('begin','end',6).'s<br>';
        echo debug('begin','end','m');
    }
    
    //蜘蛛池导入
    public function putSpiderRedis(){
        $redis = new RedisController('sync');
        //$data = ;
        $data = [];
        foreach ($data as $value){
            $redis->setSetValue('spider', $value);
            echo $value . '-';
        }
    }

    //临时运行一次清除
    public function del(){
        $result = (new RedisController())->delPrefixRedis('success_');
        $result = (new RedisController())->delPrefixRedis('failed_');
        $curl_url = (new RedisController)->redisSetStringValue('curl_url');
        $switch = file_get_contents('http://'. $curl_url . '/delproxy');
        echo '删除成功';
    }

    //远程更改爬虫系统帐户
    public function switchZmAccounts(){
        $curl_url = (new RedisController)->redisSetStringValue('curl_url');
        $switch = file_get_contents('http://'. $curl_url . '/zmaccounts');
        if ($switch == 'success'){
            echo '切换代理帐户成功';
        }
    }

    //批量删除前一天的邮件
    public function delMail(){
        //关闭转发功能
        /*	    $bt = new BtEmailServer();
                $transpond_email_list = $bt->getTranspondEmailList();
                $transpond_email_number = 0;
                if (count($transpond_email_list) > 0){
                    foreach ($transpond_email_list as $value){
                        $bt->deleteTranspondEmail('recipient', $value['user'], $value['forward_user']);
                        $transpond_email_number++;
                    }
                }*/
        $redis = new RedisController('sync');
        $mails = $redis->getSetAllValue('mail_domain');
        foreach ($mails as $value){
            $result = (new MailController())->getMailBoxs($value);
            if ($result > 0){
                echo $value . '清理'.$value.'数量：' . $result['del_count'] . ' / 邮箱总数：' . $result['count'] . '*****';
            }else{
                echo $value . $result;
            }
        }
    }

    //保存前一天的采集记录json保存到数据库
    public function SaveYesterday(){
        $result = (new QueueController())->saveYesterday();
        if ($result){
            $data['value_json'] = $result;
            $id = (new HistoryModel())->createValue($data);
            if ($id > 0){
                $redis = new RedisController('sync');
                $success = $redis->keys('success_');
                $redis->del($success);
                return show('保存成功');
            }else{
                return show('保存失败', '', 4000);
            }
        }else{
            return show('获取数据失败', '', 4000);
        }
    }

    //远程更改蜘蛛采集pingme key
    public function pingmeKey(){
        $curl_url = (new RedisController)->redisSetStringValue('curl_url');
        $pingme_key = file_get_contents('http://'. $curl_url . '/pingme');
        if (strlen($pingme_key) == 32){
            echo $pingme_key;
        }else{
            echo '采集失败';
        }
    }

    //生成sitemap
    /*    public function sitemap(){
            $website = Request::param('website');
            if (!$website){
                return show('未指明站点,如:mytempsms.com/www.yinsiduanxin.com');
            }
            $result = (new SitemapMytempsmsController())->create($website);
            return $result;
        }

        public function intest(){
            return 1;
        }*/


    public function sendgmail(){
        $email = "lastchiliarch@163.com";
        //var_dump(checkdnsrr(array_pop(explode("@",$email)),"MX"));
    }

    public function autoSpider(){
        $str = '';
        for ($i = 1; $i < 3; $i++){
            $str .= $this->spiderUpdate($i);
        }
        echo $str;
    }
    public function spiderUpdate($id){
        //$id = input('get.id');
        $project = [
            '1' => [
                'url' => 'https://receive-sms.com',
                'warehouse_id' => 11,
                'pattern' => '/SMS\">(.*?)</'
            ],
            '2' => [
                'url' => 'https://www.receivesmsonline.net',
                'warehouse_id' => 10,
                'pattern' => '/47px\"\>\+(.*?)<\/h2/'
            ],
            '3' => [
                'url' => 'https://jiemahao.com',
                'warehouse_id' => 25,
                'pattern' => '/47px\"\>\+(.*?)<\/h2/'
            ]
        ];
        $current = $project[$id];
        //通过代理采集
        $html = curl_get($current['url']);
        preg_match_all($current['pattern'], $html, $number);
        $number = $number[1];
        //dump($number);
        if(!$number){
            return '采集失败';
        }
        $phone_model = new PhoneModel();
        $warehouse_phone = $phone_model->getWarehouseAll($current['warehouse_id'], 'phone_num');
        //dump($warehouse_phone);
        $number_new = [];
        for($i = 0; $i < count($number); $i++){
            if ($id == 3){
                $number[$i] = $this->trimall($number[$i]);
            }
            if (substr($number[$i], 0, 1) == 1){
                $number[$i] = substr($number[$i], 1);
                $country_id = 3;
                $number_new[$i] = $number[$i];
            }elseif(substr($number[$i], 0, 2) == 44){
                $number[$i] = substr($number[$i], 2);
                $country_id = 2;
                $number_new[$i] = $number[$i];
            }elseif (substr($number[$i], 0, 2) == 86){
                $number[$i] = substr($number[$i], 2);
                $country_id = 1;
                $number_new[$i] = $number[$i];
            }
            //判断数据库是否存在
            $phone = $phone_model->getPhoneValue($number[$i], 'id');
            if (!$phone){
                $data = [
                    'phone_num' => $number[$i],
                    'country_id' => $country_id,
                    'warehouse_id' => $current['warehouse_id'],
                    'online' => 1,
                    'show' => 1
                ];
                $create_result = $phone_model->createPhone($data);
                if ($create_result > 0){
                    echo '('.$current['warehouse_id'].')' . ($i+1) . '★' . $number[$i] . '新增成功';
                }
            }else{
                echo '('.$current['warehouse_id'].')' . ($i+1) . '⊙' . $number[$i] .$country_id .'||';
            }
        }

        //比对新号码，判断是否下架 $number_old $number
        foreach ($warehouse_phone as $old){
            if (!in_array($old, $number)){
                $result = $phone_model->check01($old, 'online', 0);
                $phone_model->check01($old, 'sort', -9);
                if ($result){
                    echo '('.$current['warehouse_id'].')' . '==' . $old . '号码重复，已成功下线';
                }
            }
        }
    }

    //删除文本中的空格，用于删除接码号中的空格
    function trimall($str){
        $qian=array(" ","　","\t","\n","\r");
        return str_replace($qian, '', $str);
    }

    //更新数据库
    function bak(){
        $phone_arr = Db::table('phone')->where('show', '=', 1)->column('id');
        for ($i = 0; $i < count($phone_arr); $i++){
            Log::write('开始写入...' . $phone_arr[$i],'notice');
            $old_data = Db::table('collection_msg')->where('phone_id', '=', $phone_arr[$i])->limit(1000)->order('id', 'desc')->select();
            Db::table('collection_msg_bak')->insertAll($old_data);
            Log::write('写入新库完成' . $phone_arr[$i],'notice');
        }
        Log::write('success','notice');
    }

    //清除防火墙Ip
    function delFirewall(){
        $bt = new Bt();
        $firewale_list = $bt->getFireWallPage(10, 10);
        foreach ($firewale_list['data'] as $value){
            //判断是否为ip地址
            if(preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $value['port']))
            {
                $result = $bt->DelDropAddress($value['id'], $value['port']);
                if ($result['status']){
                    echo $value['port'] . '删除成功';
                }
            }
            //sleep(10);
        }
    }

    //号码点击次数更新，从redis批量写入数据库
    public function phoneClickUpdateSql(){
        $type = input('get.type');

        $redis = new RedisController();
        $redis_key = 'phone_click';
        $click_data = $redis->hGetAll($redis_key);
        $number = 0;
        foreach ($click_data as $phone => $value){
            if ($value > 0){
                if ($type){
                    //远程更新数据到redis
                    $update = (new RedisController('master'))->hIncrby($redis_key, $phone, $value);
                    if ($update){
                        (new RedisController())->hSet($redis_key, $phone, 0);
                    }
                }else{
                    $update = Db::connect('db_master_write')->name('phone')->where('phone_num', '=', $phone)->setInc('total_num', $value);
                    if ($update){
                        $redis->hSet($redis_key, $phone, 0);
                    }
                }

                $number++;
            }

        }

        if ($number > 0){
            echo '号码点击率更新成功'.$number.'个';
        }else{
            echo '号码点击率更新异常';
        }
    }
    
    //从服务器redis success数据同步到主服务器redis
    public function syncRedisSuccess(){
        //1.获取到redis local里面存在的success数据
        //2.把数据远程累加到master redis里面
        $redis = new RedisController();
        $success = $redis->keys('success');
        $success_total = 0;
        foreach ($success as $value){
            $num = (new RedisController())->get($value);
            $result = (new RedisController('master'))->incrBy($value, $num);
            if ($result){
                (new RedisController())->del($value);
                $success_total++;
            }
        }
        if ($success_total == count($success)){
            echo 'redis远程入库成功+' . $success_total;
        }
    }

    //自动解封IP
    public function unfreezeIP(){
        $ip = Request::param('ip');
        if (!$ip){
            return 'fail';
        }
        $redis = new RedisController();
        //查看该ip是否存在 hvals ip:click:$ip
        $result = $redis->hSetTtl("ip:click:" . $ip, 'score', 0.5);
        return json_encode($result);
    }


    /**
     * 上传电脑心跳检测
     * type = up 上传信息
     * type = check 心跳检测
     */
    public function computerHeart(){
        $redis = new RedisController('sync');
        $type = input('get.type');
        if ($type == 'check'){
            //心跳检测
            $heart_time = $redis->redisCheck('computer_heart');
            if ((time() - $heart_time) > 300){
                (new \app\common\controller\MailController())->noticeMail("上传服务器故障，请火速检查处理");
                echo '心跳检测失败，上传服务器异常，发送通知邮件成功';
            }else{
                echo '心跳检测通过，上传服务器正常';
            }
        }else{
            //心跳信息上传
            $redis->setStringValue('computer_heart', time());
        }

    }

    //ddos预案
    public function ddos(){
        $type = input('get.type');
        $ip = input('get.ip');
        if(!$type && !$ip){
            return '请设置参数,默认可设置?type=default';
        }
        if ($type == 'default'){
            $ip = '136.244.89.205';
        }
        $site = [
            [
                "url" => "https://api.cloudflare.com/client/v4/zones/ed02fdc69e5aed53580ef8aa8401df61/dns_records/2fd0c777ea7aa8f94f32116f06c29854",
                "type" => "A",
                "name" => "mytempsms.com",
                "content" => $ip,
                "ttl" => 1,
                "proxied" => true,
            ],
            [
                "url" => "https://api.cloudflare.com/client/v4/zones/ed02fdc69e5aed53580ef8aa8401df61/dns_records/18351600901121a992a8940530f6afcc",
                "type" => "A",
                "name" => "ja.mytempsms.com",
                "content" => $ip,
                "ttl" => 1,
                "proxied" => true,
            ],
            [
                "url" => "https://api.cloudflare.com/client/v4/zones/ed02fdc69e5aed53580ef8aa8401df61/dns_records/a45ccff6fc30fd58933b50333f9dc5a5",
                "type" => "A",
                "name" => "ko.mytempsms.com",
                "content" => $ip,
                "ttl" => 1,
                "proxied" => true,
            ],
            [
                "url" => "https://api.cloudflare.com/client/v4/zones/ed02fdc69e5aed53580ef8aa8401df61/dns_records/7cb702d41df38439c4a4360dd49031d4",
                "type" => "A",
                "name" => "vi.mytempsms.com",
                "content" => $ip,
                "ttl" => 1,
                "proxied" => true,
            ],
        ];
        $i = 0;
        foreach ($site as $key=>$value){
            $result = $this->ddosCurl($value['url'], $value);
            if ($result){
                $i++;
            }
        }
        $count = count($site);
        return "总共需要解析 {$count} 条，成功条数 {$i}";
    }

    public function ddosCurl($url, $param){
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'X-Auth-Email:***@gmail.com', 'X-Auth-Key:c7526b8faba52f09fb416577ccfd762fd1b18'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); //设置请求方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));//设置提交的字符串
        $res = json_decode(curl_exec($ch),true);
        curl_close($ch);
        if($res['success']){
            return true;
        }
    }
    
    //生成更新缓存链接
    public function delcache(){
        $page = input('get.page');
        $country = input('get.country');
        $lang = ['en', 'ru', 'ja', 'ko', 'es', 'fr', 'de', 'ar', 'vi', 'bd', 'it', 'ir', 'id', 'pt', 'in', 'my', 'az', 'al', 'pk', 'tr', 'th', 'za', 'mm', 'tz', 'ua', 'pl', 'uz', 'se', 'nl'];
        switch ($page){
            case 'index':
                foreach ($lang as $value){
                    if($value === 'en'){
                        $sub = '';
                    }else{
                        $sub = $value . '.';
                    }
                    echo 'https://' . $sub . 'mytempsms.com <br>';
                }
                break;
            case 'country':
                case 'country':
                foreach ($lang as $value){
                    if($value === 'en'){
                        $sub = '';
                    }else{
                        $sub = $value . '.';
                    }
                    //https://mytempsms.com/receive-sms-online/usa-phone-number.html
                    echo 'https://' . $sub . 'mytempsms.com/receive-sms-online/' . $country . '-phone-number.html <br>';
                }
                break;
        }        
        
    }
}