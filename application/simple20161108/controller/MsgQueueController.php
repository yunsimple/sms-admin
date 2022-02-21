<?php
/**
 * Created by PhpStorm.
 * Date: 2019-09-23 0023
 * Time: 21:16
 */

namespace app\simple20161108\controller;


use app\common\controller\RedisController;
use think\Controller;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;
use app\api\controller\ApiController;
use think\Db;
use think\Exception;
use app\common\model\CollectionMsgModel;
use app\common\model\PhoneModel;
use think\facade\Log;

class MsgQueueController extends Controller
{

    public function heartBeat(){
        return 1;
    }

    //根据实际号码，获取uid
    protected function getPhoneDetailByPhone($phone){
        $result = Db::table('phone')
            ->where('phone_num', $phone)
            //->cache(60*60)
            ->field('uid,phone_num')
            ->select();
        if (count($result) == 1){
            return $result[0]['uid'];
        }else{
            return false;
        }
    }

    //接收上游转发过来的号码进行sync入库
    public function receiveNumber(){
        if (!Request::isPost()){
            return false;
        }
        $data = input('post.');
        if (config('database.domain') == 'best20161108'){
            $prefix = Config::get('cache.prefix');
            $phone_num = $this->getPhoneDetailByPhone($data[0]['PhoNum']);
            $messageKey = $prefix . 'message:';
            $scoreKey = $messageKey . $phone_num . '_score';
            $incKey = $prefix . 'phone_receive';
        }else{
            $phone_num = $data[0]['PhoNum'];
            $messageKey = '';
            $scoreKey = 'msg_' . $phone_num . '_score';
            $incKey = 'phone_receive';
        }
        if (!$phone_num){
            return '号码不存在';
        }
        //获取的短信数组每条循环写入到redis里面
        //采集有序集合的方式,每条记录给一个分数
        $redis = new RedisController('sync');
        $number = count($data);
        //dump($data);
        for ($i = 1; $i < $number+1; $i++) {
            $redis->zAdd($messageKey . $phone_num, $redis->getRedisSet($scoreKey), serialize($data[$number-$i]));
        }
        $redis->hIncrby($incKey, $phone_num);
        //如果集合内数据超过50条,就把该条数据加入队列入库处理
        $number = $redis->checkZset($messageKey . $phone_num);

        if ($number > 20) {
            //加入待处理列表
            $redis->setSetValue($messageKey . 'msg_queue', $phone_num);
        }
    }

    //如果请求错误，回调通知，删除本地单个号码请求频率限制
    public function callbackCurlFailNumber(){
        $phone_num = input('post.phone_num');
        if (!$phone_num){
            return show('参数异常');
        }
        if (config('database.domain') == 'best20161108'){
            $uid = (new PhoneModel())->getUidPhone($phone_num, 'phone_num');
            $key_phone_click = Config::get('cache.prefix') . 'click:' . $uid;
        }else{
            $key_phone_click = 'click:' . $phone_num;
        }
        return (new RedisController())->del($key_phone_click);
    }

    //接收上游转发过来的号码进行sync入库 -- MYS
    public function receiveNumberMys(){
        if (!Request::isPost()){
            return false;
        }
        $data = input('post.');
        $phone_num = $data['PhoNum'];
        if (!$phone_num){
            return '号码不存在';
        }
        //获取的短信数组每条循环写入到redis里面
        //采集有序集合的方式,每条记录给一个分数
        $messageKey = Config::get('cache.prefix') . 'message:';
        $redis = new RedisController('sync');
        $number = count($data);
        //dump($data);
        for ($i = 1; $i < $number+1; $i++) {
            $redis->zAdd($phone_num, $this->getRedisSet('msg_' . $phone_num . '_score'), serialize($data[$number-$i]));
        }
        $redis->hIncrby('phone_receive', $phone_num);
        //如果集合内数据超过50条,就把该条数据加入队列入库处理
        $number = $redis->checkZset($phone_num);

        if ($number > 20) {
            //加入待处理列表
            $redis->setSetValue('msg_queue', $phone_num);
        }
    }

    //如果请求错误，回调通知，删除本地单个号码请求频率限制
    public function callbackCurlFailNumberMys(){
        $phone_num = input('post.phone_num');
        if (!$phone_num){
            return false;
        }
        $key_phone_click = 'click:' . $phone_num;
        return (new RedisController())->del($key_phone_click);
    }

    //易语言本地写入redis数据
    public function insertLocalSms($PhoNum = null, $smsNumber = null, $smsContent = null){
        //post提交或者控制器内提交
        if (!$PhoNum){
            $PhoNum = Request::param('PhoNum');
            $smsNumber = Request::param('smsNumber');
            $smsContent = trim(Request::param('smsContent'));
        }
        $smsDate = time();

        if (!$smsDate && !$PhoNum && !$smsNumber && !$smsContent){
            return show('访问异常，请稍候再试...', '', 4444, 304);
        }
        $PhoNum = $this->getPhoneDetailByPhone($PhoNum);
        $project_content = $smsContent;
        //trace($project_content, 'notice');
        //smsContent过滤
        $smsContent = $this->filterKey($smsContent);

        $data[0]['smsDate'] = $smsDate;
        $data[0]['PhoNum'] = $PhoNum;
        $project = $this->smsNumber($project_content);
        //trace('$project:' . $project, 'notice');
        $data[0]['smsNumber'] = $smsNumber;
        $data[0]['smsContent'] = $smsContent;
        $data[0]['url'] = $project;
        //file_get_contents("https://api.telegram.org/sdf:sdf/sendmessage?text=".$smsContent."&chat_id=sdf");
        $api = new ApiController;
        $result = $api->msgRedis($PhoNum, $data);
        if (count($result) > 0){
            return show('success');
        }else{
            return show('fail');
        }
    }

    /**
     *本地手机上传短信
     * ['from' => '1069483381111',
     * 'content' => 'SIM2_86-1658888888@@【顺丰速运】您此次查询的验证码为595085',]
     */
    public function insertPhone(){
        $sms_data = input('post.');
        if(!$sms_data){
            return show('fail');
        }
        //Log::record($sms_data, 'notice');
        $detail = explode('@@', $sms_data['content']);
        $content = $detail[1];
        preg_match('/[0-9]{5,}/', $detail[0], $matchs);
        $phone = $matchs[0];
        return $this->insertLocalSms($phone, $sms_data['from'], $content);
    }




    /**
     * 上传电脑心跳检测
     * type = up 上传信息
     * type = check 心跳检测
     */
    public function computerHeart(){
        $redis = new RedisController();
        $type = input('get.type');
        if ($type == 'check'){
            //心跳检测
            /*$heart_time = $redis->redisCheck('computer_heart');
            if ((time() - $heart_time) > 60){
                (new \app\common\controller\MailController())->noticeMail("上传服务器故障，请火速检查处理");
            }*/
        }else{
            //心跳信息上传
            $result = $redis->setStringValue('computer_heart', time());
            if($result){
                return 'success';
            }else{
                return 'fail';
            }
        }

    }

    /**
     * https://www.atlascommunications.co/回调
     * 'id' => 'e56ca3c5-45f0-43ab-ad6f-659b9ed8f5bd',
     * 'from' => 'Alibaba',
     * 'message' => '【优酷土豆】您的短信验证码是662250。您的手机号正在使用随机密码登录服务，如非本人操作，请尽快修改密码。',
     * 'to' => '447458196598',
     */
    public function callbackATLAS(){
        //return 1;
        $sms_data = Request::param();
        //Log::record($sms_data, 'notice');
        //halt($sms_data);
        if (!array_key_exists('to', $sms_data) || !array_key_exists('from', $sms_data) || !array_key_exists('message', $sms_data)){
            return show('fail');
        }
        $phones = $this->areaCode($sms_data['to']);
        $PhoNum = $phones['phone'];
        $PhoNum = $this->getPhoneDetailByPhone($PhoNum);
        $smsNumber = $sms_data['from'];
        $smsContent = trim($sms_data['message']);
        $result = $this->insertATLAS($PhoNum, $smsNumber, $smsContent);
        return $result;
    }

    public function insertATLAS($PhoNum, $smsNumber, $smsContent){
        $smsDate = time();
        $project_content = $smsContent;

        //smsContent过滤
        $smsContent = $this->filterKey($smsContent);

        $data[0]['smsDate'] = $smsDate;
        $data[0]['PhoNum'] = $PhoNum;
        $project = $this->smsNumber($project_content);
        //halt($project);
        $data[0]['smsNumber'] = $smsNumber;
        $data[0]['smsContent'] = $smsContent;
        $data[0]['url'] = $project;
        $api = new ApiController;
        $result = $api->msgRedis($PhoNum, $data);
        if (count($result) > 0){
            return show('success');
        }
    }


    public function areaCode($phone){
        if (!$phone){
            return false;
        }
        $code = [44,33,31,46,1,852];
        foreach ($code as $value){
            $code_length = strlen($value);
            $phone_code = substr($phone, 0, $code_length);
            if ($value == $phone_code){
                return ['area_code' => $phone_code, 'phone' => substr($phone, $code_length)];
                break;
            }
        }
        return false;
    }

    //过滤关键字
    public function filterKey($smsContent, $type = 'ysdx'){
        if ($type == 'ysdx'){
            $key = [
                '银行',
                '政府',
                '微信',
                '支付',
                '京东',
                '贷',
                '政务',
                '保险',
                '分期',
                '淘宝网',
                '财险',
                '腾讯科技',
                'Tencent',
                '腾讯云',
                '阿里巴巴',
                '疫情',
                '卫生健康委',
                '公安',
                '交警',
                '交管12123',
                '网上办事大厅',
                '付款',
                '八達通',
                '退款',
                '市长热线',
                '银联商务',
                '中国银联',
                '交易猫',
                '小店',
                '医保',
                '信访',
                '管理局',
                '电信',
                '移动',
                '联通',
                '苏宁',
                '人寿',
                'WeChat',
                '阿里云',
                '武汉房管',
                '人社局',
                '话机通信',
                '借款大王',
                '广东省教育考试院',
                '广东省统一身份认证平台',
                '900954',
                '53898',
                '网上国网',
                '中国平安',
                '省建设信息中心',
                'Taobao',
                'KnowRoaming',
                '朗玛移动',
                '统一身份认证',
                '新嚎',
                'bbest1.me',
                '33400u',
                '12345',
                '财付通',
                'gov.cn',
                '深圳农商行',
                '证券',
                '招联金融',
                '江西财政系统',
                '交银施罗德基金',
                '珠海人社',
                '平安健康险',
                '85560',
                '申请了搜索推广短信验证',
                '市场监管总局',
                '社保',
                '791838',
                '彩金',
                '蝙蝠APP',
                '市场监管局',
                '太平洋产险',
                '海关总署',
                '幸运儿',
                'CCB建融家园',
                '信息中心',
                '新一花',
                '淘宝特价版',
                '云曼信息',
                '金山金融',
                '车管所',
                '阿里小号',
                'JVID',
                '省联合征信',
                '国家反诈中心',
                '闪 银',
                'Alipay',
                '百度',
                '通信平台'
            ];
        }else{
            $key = [
                '银行',
                '政府',
                '微信',
                '京东',
                '政务',
                '保险',
                '淘宝网',
                '腾讯科技',
                '腾讯云',
                '阿里巴巴',
                '卫生健康委',
                '公安',
                '交警',
                '交管12123',
                '网上办事大厅',
                '市长热线',
                '银联商务',
                '中国银联',
                '信访',
                '人寿',
                'WeChat',
                '阿里云',
                '武汉房管',
                '人社局',
                '广东省教育考试院',
                '广东省统一身份认证平台',
                '网上国网',
                '中国平安',
                '省建设信息中心',
                'Taobao',
                '统一身份认证',
                '12345',
                '财付通',
                'gov.cn',
                '深圳农商行',
                '证券',
                '招联金融',
                '江西财政系统',
                '交银施罗德基金',
                '珠海人社',
                '平安健康险',
                '申请了搜索推广短信验证',
                '市场监管总局',
                '社保',
                '市场监管局',
                '太平洋产险',
                '海关总署',
                'CCB建融家园',
                '信息中心',
                '新一花',
                '淘宝特价版',
                '云曼信息',
                '金山金融',
                '车管所',
                '阿里小号',
                'JVID',
                'Alipay'
            ];
        }
        for ($b = 0; $b < count($key); $b++){
            $exist = stristr($smsContent, $key[$b]);
            if ($exist){
                $smsContent = '已屏蔽';
                return $smsContent;
                break;
            }
        }
        return $smsContent;
    }

    protected function smsNumber($smsContent){
        //情况1
        preg_match("/【(.*?)】/", $smsContent, $project);
        if (count($project) > 1){
            $project = $project[1];
            return $project;
        }
        //情况3
        $project_list = [
            'Netease',
            '微博',
            '华为',
            'RED',
            'BLK',
            '小米',
            'Grindr',
            'Twilio',
            'Love Island USA',
            'Amazon',
            'donotpay',
            'Dott',
            'Taobao',
            'Brasil TV',
            'MaxEnt',
            'Твиттере',
            'Tinder',
            'GoFundMe',
            'BIGO',
            'Apple',
            'Trump 2020',
            'foodpanda',
            'MyCom',
            'BlaBlaCar',
            'Facebook',
            'Proton',
            'JKF',
            'Huawei',
            'periscope',
            'imo',
            'Plowz',
            'Instagram',
            'OTP',
            'Telegram',
            'Grasshopper',
            'melo',
            'Google',
            'Kwai',
            'eGifter',
            'Sermo',
            'Netflix',
            'Empower',
            '豆瓣',
            'Discord',
            'Flipkart',
            '探探',
            '亚马逊',
            'Chowbus',
            'TamTam',
            'Chispa',
            'PayPal',
            'Anonymous Talk',
            'Skout',
            'WeChat',
            'JustDating',
            'WIND',
            'Uber',
            'Zoodealio',
            'HeyTap',
            'OPPO',
            'Chowbus',
            'Fastmail',
            'IKOULA',
            'WhatsApp',
            'SheerID',
            'TopstepTrader',
            'Instanumber',
            'Snapchat',
            'eBay',
            '领英',
            'Crypto',
            'Coinbase',
            'Numero',
            'Philo',
            'NVIDIA',
            'NetDragon',
            'RebateKey',
            'LuckyLand',
            'OffGamers',
            'BatChat',
            'Snibble',
            'Bumble',
            'Bolt',
            'dynamic',
            'YouTube',
            'NIKE',
            'Likee',
            'HubPages',
            'Pokreface',
            'Google Voice',
            'codigo',
            'TAIKAI',
            'Crowdtap',
            'Microworkers',
            'SIGNAL',
            'Stripe',
            'Baidu',
            'icabbi',
            'Coinut',
            'NightFury',
            'happn',
            'iHerbVerification',
            'GamerMine',
            'Depop',
            'Swvl',
            'iPayYou',
            'withlive',
            'Amuse',
            'Raise',
            'Megvii',
            'Tencent Cloud',
            'Kamatera',
            'Viber',
            'Postmates',
            'magic',
            'Pinecone',
            'adidas',
            'QuadPay',
            'Dingtone',
            'ShopWithScrip',
            'TradingView',
            'Fastmail',
            'Testin',
            'PaliPali',
            'VipSlots',
            '聊寓',
            'FAX.PLUS',
            'Wish',
            'Textline',
            'Banxa',
            'Yubo',
            'Skillz',
            'Juiker',
            'BeFrugal',
            'HelloYo',
            'DIDforSale',
            'SimplexCC',
            'Parler',
            'Gemini',
            'Valued',
            'Roomster',
            'OkCupid',
            'Twitter',
            'Microsoft',
            'Gmu',
            'Transocks',
            'Yahoo',
            'TopstepTrader',
            'FanPlus',
            'verit',
            'FIORRY',
            'Paxful',
            'Wicket',
            'Gecko',
            'CLiQQ',
            'VulkanVegas',
            'Naver',
            'Letstalk',
            'MeWe',
            'SHOPEE',
            'Aircash',
            'LinkedIn',
            'Sendinblue',
            'ICQ',
            'NEVER',
            'PinaLove',
            'waves',
            'MailRu',
            'Libon',
            '多益网络',
            'GetResponse',
            'OkCupid',
            'AstroPay',
            'G2A',
            'LocalBitcoins',
            'Imgur',
            'PaddyPower',
            'Heymandi',
            'Tagged ',
            'TalkU',
            'Upward',
            'AfreecaTV',
            'Oracle',
            'dcard',
            '优步',
            'LetyShops',
            'Indeed',
            'OnlyTalk',
            'Mob',
            'Mercari',
            'Tandem',
            'CokeVending',
            'Klook',
            'Zomato',
            'Zoho',
            'Klarna',
            'Hinge',
            'Feeld',
            'Skype',
            'Stripe',
            'Xfinity',
            'Vivaldi',
            'Paperspace',
            'Benzinga',
            'Aadhan',
            'inDriver',
            'SweetRing',
            'Zomato',
            'GAC',
            'Clubhouse',
            '全聯行動會員',
            '清北网校',
            'AstroPay',
            'NCSOFT',
            'VulkanBet',
            'Here',
            'Twoj',
            'QPP',
            'VK',
            'KakaoTalk',
            'ZEPETO',
            'Rumble',
            'Vero',
            'Freelancer',
            'WAVE',
            'STORMGAIN',
            'VOI',
            'Getir',
            'Opinion Outpost',
            'Tiki',
            'AttaPoll',
            'Lime',
            'GameStake',
            'Sorare',
            'Nevada Win',
            'Datanyze',
            'Samsung',
            'Veefly',
            'gamesofa',
            'Zam',
            'Kobiton',
            'Escort Advisor',
            'Telavita',
            'CloudSigma',
            'JUUL',
            'Apollo',
            'GAMIVO',
            'LINK',
            'Dundle',
            'Pret',
            'Gib',
            'Snappy',
        ];
        for ($i = 0; $i < count($project_list); $i++){
            $exist = stristr($smsContent, $project_list[$i]);
            if ($exist){
                return $project_list[$i];
                break;
            }

        }

        //情况2
        preg_match("/\[(.*?)\]/", $smsContent, $project1);
        if (count($project1) > 1){
            $project1 = $project1[1];
            return $project1;
        }
        //情况2
        preg_match("/\<(.*?)\>/", $smsContent, $project2);
        if (count($project2) > 1){
            $project2 = $project2[1];
            $project2 = str_replace('', '', $project2);
            return $project2;
        }

        return '';
    }

    public function insertGoogleVoiceSms(){
        $PhoNum = Request::param('PhoNum');
        $smsContent = Request::param('smsContent');
        $smsDate = Request::param('smsDate');
        $smsNumber = Request::param('smsNumber');
        //Log::write('PhoNum:' . $PhoNum . '--smsContent:' . $smsContent . '--smsNumber:' . $smsNumber . '--smsDate:' . $smsDate, 'notice');
        //Log::write('PhoNum:' . $PhoNum . '--smsContent:' . $smsContent,'notice' . '--smsNumber:' . $smsNumber . '--smsDate:' . $smsDate);
        return 'success';
    }
    //msg采集到后添加队列处理
    public function msgSaveDbQueue()
    {
        //查询列表内是否有数据
        $redis = new RedisController();
        $value = $redis->getSetAllValue('msg_queue');
        if ($value) {
            //随机取出一个号码,留20条数据,其他的全部放入数据库
            $number = 0;
            for ($i = 0; $i < count($value); $i++) {
                $data = $redis->getZsetScore($value[$i]);
                $data_number = count($data);
                if ($data_number > 0) {
                    echo $value[$i] . '----提取成功' . $data_number . '条----';
                    //把提取出来的数据存入数据库
                    $batch_data = $this->msgBatchData($value[$i], $data);
                    //dump($batch_data);
                    try {
                        $create_data = (new CollectionMsgModel())->batchCreate($batch_data);
                    }catch (Exception $e){
                        $redis->deleteZset($value[$i]);
                        continue;
                    };
                    $create_number = count($create_data);
                    if ($create_number > 0) {
                        //echo '----写入数据库成功' . $create_number . '条';
                        $number = $number + $create_number;
                        //数据写入本地备用数据库
                        /*$local_result = (new CollectionMsgModel())->batchCreate($batch_data, 'local');
                        if (count($local_result) <= 0){
                        	echo '----写入本地备用数据库失败';
                        }*/
                        //删除提取出来的数据
                        $delete_number = $redis->deleteZset($value[$i]);
                        if ($delete_number > 0) {
                            //echo '----删除成功' . $delete_number . '条';
                            //把当前号码移出集合
                            $delete_set_phone = $redis->deleteSet('msg_queue', $value[$i]);
                            if ($delete_set_phone > 0) {
                                //echo '----号码已经移出队列----' .date('Y-m-d H:i:s').'<br>';
                            } else {
                                echo $value[$i] . '----号码未能成功移出队列----' .date('Y-m-d H:i:s').'<br>';
                            }
                        } else {
                            echo '----删除失败';
                        }
                    } else {
                        echo '----写入数据库失败';
                    }
                } else {
                    echo $value[$i] . '----提取失败';
                }
            }
            echo '----今日累计写入数据：' . $number . ' 条';
        } else {
            echo '并没有需要入库的数据';
        }
    }

    //msg采集到后添加队列处理
    public function clear()
    {
        //查询列表内是否有数据
        $redis = new RedisController();
        $value = $redis->getSetAllValue('msg_queue');
        if ($value) {
            //随机取出一个号码,留20条数据,其他的全部放入数据库
            $number = 0;
            for ($i = 0; $i < count($value); $i++) {
                $data = $redis->getZsetScore($value[$i]);
                $data_number = count($data);
                if ($data_number > 0) {
                    //echo $value[$i] . '----提取成功' . $data_number . '条----';
                    //把提取出来的数据存入数据库
                    $batch_data = $this->msgBatchData($value[$i], $data);
                    $create_data = 1;//(new CollectionMsgModel())->batchCreate($batch_data);
                    $create_number = 1;//count($create_data);
                    if ($create_number > 0) {
                        //echo '----写入数据库成功' . $create_number . '条';
                        $number = $number + $create_number;
                        //数据写入本地备用数据库
                        /*$local_result = (new CollectionMsgModel())->batchCreate($batch_data, 'local');
                        if (count($local_result) <= 0){
                        	echo '----写入本地备用数据库失败';
                        }*/
                        //删除提取出来的数据
                        $delete_number = $redis->deleteZset($value[$i]);
                        if ($delete_number > 0) {
                            //echo '----删除成功' . $delete_number . '条';
                            //把当前号码移出集合
                            $delete_set_phone = $redis->deleteSet('msg_queue', $value[$i]);
                            if ($delete_set_phone > 0) {
                                //echo '----号码已经移出队列----' .date('Y-m-d H:i:s').'<br>';
                            } else {
                                echo $value[$i] . '----号码未能成功移出队列----' .date('Y-m-d H:i:s').'<br>';
                            }
                        } else {
                            echo '----删除失败';
                        }
                    } else {
                        echo '----写入数据库失败';
                    }
                } else {
                    echo $value[$i] . '----提取失败';
                }
            }
            echo '----今日累计写入数据：' . $number . ' 条';
        } else {
            echo '并没有需要入库的数据';
        }
    }

    /**
     * 准备短信批量入库数据
     * arr['phone_id':111,'content':'string']
     */
    private function msgBatchData($phone_num, $data)
    {
        //获取phone_id
        $phone_id = (new PhoneModel())::where('phone_num', '=', $phone_num)->value('id');
        $arr = [];
        $number = count($data);
        for ($i = 0; $i < $number; $i++) {
            $da = unserialize($data[$i]);
            foreach ($da as $key => $value){
                if (array_key_exists('url', $da)){
                    $arr[$i]['url'] = $da['url'];
                }
            }
            $arr[$i]['phone_id'] = $phone_id;
            $arr[$i]['content'] = $data[$i];
            /*            $arr[$i]['create_time'] = time();
                        $arr[$i]['update_time'] = time();*/
        }
        return $arr;
    }

    public function forwardF4($phone, $to, $content, $f4_params){
        return false;
        if (!$phone || !$content){
            return false;
        }

        //过滤项目
        $content = $this->filterKey($content, 'f4');
        if ($content == '已屏蔽'){
            return '高风险项目';
        }

        $area_code = $this->areaCode($phone);
        $msec = msecTime();
        $secret = $f4_params['secret'];
        $sign = $msec . "\n" . $secret;
        $sign = hash_hmac("sha256", $sign, $secret, true);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        $params = [
            'from' => $to,
            'content' => $content . '@@' . 'SIM'. $f4_params['slot'] .'_' . $area_code['area_code'] . '-' . $area_code['phone'],
            'timestamp' => $msec,
            'sign' => $sign
        ];
        //dump($params);
        $result = curl_post('http://121.196.190.139:90/smsapi.php?token=' . $f4_params['device_id'], $params);
        $result = json_decode($result, true);
        if ($result['Status'] == 'SMS Received'){
            Log::record($params, 'notice');
            return show('success');
        }
        return show('fail');
    }
}