<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
/**
 * 通用API返回接口
 * @param $error_code int 是否存在错误
 * @param $message string 返回具体信息
 * @param array $data 返回数据
 * @param int $httpCode HTTP状态码
 *
 * @return \think\response\Json
 */
function show($message, $data = [],$error_code = 0, $header = [], $httpCode = 200)
{
    $result = [
        'error_code' => $error_code,
        'msg' => $message,
        'data' => $data
    ];
    //如果data没有值,$data将不显示
    if (empty($data)){
        unset($result['data']);
    }
    if ($header){
        return json($result, $httpCode)->header($header);
    }else{
        return json($result, $httpCode);
    }
}

function curl_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.4.2661.102 Safari/537.36; 360Spider";
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'. generateIP(), 'CLIENT-IP:' . generateIP())); //构造IP
    curl_setopt($ch, CURLOPT_REFERER, $url);//模拟来路
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10 );//连接超时，这个数值如果设置太短可能导致数据请求不到就断开了
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}

function curl_get($url = '') {
    if (empty($url)) {
        return false;
    }
    $szUrl = $url;
    $UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.4.2661.102 Safari/537.36; 360Spider";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $szUrl);
    curl_setopt($curl, CURLOPT_HEADER, 0);  //0表示不输出Header，1表示输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'. generateIP(), 'CLIENT-IP:' . generateIP())); //构造IP
    curl_setopt($curl, CURLOPT_REFERER, $url);//模拟来路
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10 );//连接超时，这个数值如果设置太短可能导致数据请求不到就断开了
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

/**
 * 异步curl
 * @param string $url
 * @param string $type
 * @param array $param
 * @return false|int|string
 */
function asyncRequest(string $url, string $type = 'GET', array $param = [])
{
    $url_info = parse_url($url);
    $host = $url_info['host'];
    $path = $url_info['path'];
    if ($type == 'POST'){
        $query = isset($param) ? http_build_query($param) : '';
    }
    $port = 80;
    $errno = 0;
    $errstr = '';
    $timeout = 30; //连接超时时间（S）

    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    //$fp = stream_socket_client("tcp://".$host.":".$port, $errno, $errstr, $timeout);

    if (!$fp) {
        logs('连接失败', 'async_request_logs');
        return '连接失败';
    }

    if ($errno || !$fp) {
        logs($errstr, 'async_request_logs');
        return $errstr;
    }

    stream_set_blocking($fp, 0); //非阻塞
    stream_set_timeout($fp, 10);//响应超时时间（S）
    $out = $type . ' ' . $path . " HTTP/1.1\r\n";
    $out .= "host:" . $host . "\r\n";
    if ($type == 'GET'){
        $out .= "connection:close\r\n\r\n";
    }else{
        $out .= "content-length:" . strlen($query) . "\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $query;
    }
    $result = @fputs($fp, $out);
    usleep(1000); // 延迟1毫秒，如果没有这延时，可能在nginx服务器上就无法执行成功
    @fclose($fp);
    return $result;
}

function generateIP(){
    $ip2id= round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
    $ip3id= round(rand(600000, 2550000) / 10000);
    $ip4id= round(rand(600000, 2550000) / 10000);
    //下面是第二种方法，在以下数据中随机抽取
    $arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
    $randarr= mt_rand(0,count($arr_1)-1);
    $ip1id = $arr_1[$randarr];
    return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}

//使用cdn后获取真实Ip
function real_ip(){
	if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    		$ip = $_SERVER["REMOTE_ADDR"];
    	}else {
    		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    	}
    return $ip;
}

//判断蜘蛛
function is_crawler() {
    $agent= strtolower($_SERVER['HTTP_USER_AGENT']);
    if (!empty($agent)) {
        $spiderSite= array(
            "Sogou",
        	"SemrushBot",
        	"360Spider",
        	"YisouSpider",
        	"bingbot",
        	"JikeSpider",
        	"EasouSpider",
            "TencentTraveler",
            "Baiduspider",
            "BaiduGame",
            "Googlebot",
            "msnbot",
            "Sogouwebspider",
            "Sosospider+",
            "Sogou web spider",
            "ia_archiver",
            "Yahoo! Slurp",
            "YoudaoBot",
            "Yahoo Slurp",
            "MSNBot",
            "Java (Often spam bot)",
            "BaiDuSpider",
            "Voila",
            "Yandex bot",
            "BSpider",
            "twiceler",
            "Sogou Spider",
            "Speedy Spider",
            "Google AdSense",
            "Heritrix",
            "Python-urllib",
            "Alexa (IA Archiver)",
            "Ask",
            "Exabot",
            "Custo",
            "OutfoxBot/YodaoBot",
            "yacy",
            "SurveyBot",
            "legs",
            "lwp-trivial",
            "Nutch",
            "StackRambler",
            "The web archive (IA Archiver)",
            "Perl tool",
            "MJ12bot",
            "Netcraft",
            "MSIECrawler",
            "WGet tools",
            "larbin",
            "Teoma",
            "Fish search",
            'AhrefsBot',
        );
        foreach($spiderSite as $val) {
            $str = strtolower($val);
            if (strpos($agent, $str)){
                return true;
            }
        }
    } else {
        return false;
    }
}

/**
 * 获取子域名
 * @return string
 */
function get_subdomain(){
    $sub_domain = \think\facade\Request::subDomain();
    if (empty($sub_domain)){
        $sub_domain = 'www';
    }
    return $sub_domain;
}

/**
 * 获取子域名
 * @return string
 */
function get_domain(){
    $domain = \think\facade\Request::rootDomain();
    $domain = explode('.', $domain);
    return $domain[0];
}

/**
 * 移动端判断
 */

function is_mobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
    {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}

/**
 * 获取随机位token字符
 */
function getRandChar($length)
{
    $str = null;
    $strPol = "A0B1C3D5E9cGHIJeKLq5MN7PQR63STeytUV9aWXYZ012aB345C6Q789abcdefFghGijkAlmn6opqErstuDvwxSyz";
    $max = strlen($strPol) - 1;
    for($i=0 ; $i<$length ; $i++){
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

/**
 * 获取随机位token字符
 */
function getRandNum($length)
{
    $str = null;
    $strPol = "55987412032659874512355487896321587452123002365987451254632598874514523659874120325698445";
    $max = strlen($strPol) - 1;
    for($i=0 ; $i<$length ; $i++){
        //$str .= $strPol[rand(0, $max)];
        $num = $strPol[rand(0, $max)];
        if($i == 0 && $num == 0){
            $num = 8;
        }
        $str .= $num;
    }
    return $str;
}

//判断时间是否在某一区间
function time_section($begin, $end)
{
    $checkDayStr = date('Y-m-d ',time());
    //return $checkDayStr;
    $timeBegin1 = strtotime($checkDayStr. $begin .":00");
    $timeEnd1 = strtotime($checkDayStr. $end .":00");

    $curr_time = time();

    if($curr_time >= $timeBegin1 && $curr_time <= $timeEnd1)
    {
        return true;
    }
    return false;
}

//判断一个字符串是否存在,不区分大小写
function strIsExist($str, array $str_arr){
    foreach ($str_arr as $key=>$value){
        $exist = stristr($str, $str_arr[$key]);
        if ($exist){
            return true;
        }
    }
    return false;
}

/**
 * ip2region库返回拼接数据
 */
function getIpRegion($region)
{
    if (!$region) {
        return false;
    }
    $regions = explode('|', $region);
    $val = '';
    foreach ($regions as $value) {
        if ($value) {
            $val = $val . $value;
        }
    }
    if ($val) {
        return $val;
    } else {
        return false;
    }
}

/**
 * 返回间隔时间多少秒
 * 计划之前的时候，$t 取反值即可
 * @param $time 需要比较的时间撮
 * @param string $lang 中文/英文
 * $type 计算之前还是之后 later
 * @return string
 */
function gap_times($time, $lang = 'zh', $type = 'later')
{
    if($type == 'later'){
        $zh_later_value = '后';
        $en_later_value = ' later';
        $t = $time - time();
    }else{
        $zh_later_value = '前';
        $en_later_value = ' ago';
        $t = time() - $time;
    }
    $langs = [
        ['zh' => '年', 'en' => 'year'],
        ['zh' => '个月', 'en' => 'months'],
        ['zh' => '星期', 'en' => 'week'],
        ['zh' => '天', 'en' => 'day'],
        ['zh' => '小时', 'en' => 'hour'],
        ['zh' => '分钟', 'en' => 'minutes'],
        ['zh' => '秒', 'en' => 'second'],
        ['zh' => $zh_later_value, 'en' => $en_later_value],
    ];
    $f = array(
        '31536000'=> $langs[0][$lang],
        '2592000' => $langs[1][$lang],
        '604800'  => $langs[2][$lang],
        '86400'   => $langs[3][$lang],
        '3600'    => $langs[4][$lang],
        '60'      => $langs[5][$lang],
        '1'       => $langs[6][$lang],
    );
    foreach ($f as $k=>$v){
        if (0 != $c = floor($t / (int)$k)){
            if ($c > 0){
                return $c . ' ' . $v . $langs[7][$lang];
            }else{
                return 0;
            }
        }
    }
}

/**
 * gethostbyaddr识别蜘蛛
 * @param $ip
 * @param $agent
 * @param $hostname
 * @return bool
 */
function checkSpider($ip)
{
    //1.正则预判ip段是否为蜘蛛
    //2.判断redis蜘蛛池是否存在记录
    //3.进行agent校验
    //4.根据gethostbyaddr获取反向dns值
    //5.判断gethostbyaddr获取到的dns值
    //6.如果判断为蜘蛛写入redis蜘蛛池
    if (isset($_SERVER['HTTP_USER_AGENT'])){
        $agent = $_SERVER['HTTP_USER_AGENT'];
    }else{
        return false;
    }
    if (regSpider($agent)){

/*        //蜘蛛IP段检测
        if (isRobotIP($ip)){
            return true;
        }*/

        //redis蜘蛛池检测
        if ((new \app\common\controller\RedisController('sync'))->sIsMember('spider', $ip)){
            return true;
        }

        //反向dns检测
        $rdns = gethostbyaddr($ip);
        if (regSpider($rdns)){
            (new \app\common\controller\RedisController('sync'))->setSetValue('spider', $ip);
            return true;
        }
    }
    return false;
}

/**
 * 正则判断字符串是否存在
 */
function regSpider($agent){
    /**
     * baiduspider-116-179-32-237.crawl.baidu.com
     * crawl-66-249-79-121.googlebot.com
     * sogouspider-49-7-21-103.crawl.sogou.com
     * hn.kd.ny.adsl  360/360Spider
     * msnbot-207-46-13-96.search.msn.com  必应/bingbot
     * ip-54-36-148-34.a.ahrefs.com
     * g1038.crawl.yahoo.net
     * 77-88-5-79.spider.yandex.com
     * shenmaspider-106-11-157-42.crawl.sm.cn 神马/YisouSpider
     * bytespider-60-8-123-10.crawl.bytedance.com 头条
     * ip-54-36-148-76.a.ahrefs.com
     */
    if (preg_match('/(?:Sogou|360Spider|YisouSpider|shenmaspider|bingbot|Baiduspider|Googlebot|msnbot|Yahoo|Yandex|MJ12bot|Teoma|Bytespider|YoudaoBot|Voila|BSpider|twiceler|Heritrix|Alexa|Ask|Exabot|Custo|OutfoxBot|yacy|SurveyBot|legs|lwp-trivial|Nutch|StackRambler|Netcraft|MSIECrawler|larbin|AhrefsBot|ahrefs|BingPreview)/iu', $agent)){
        return true;
    }
    return false;
}

//根据IP段判断是否为蜘蛛，需要更新IP段
function isRobotIP($ip) {
    if (empty($ip)){
        $ip = real_ip();
    }
    $spiderSite= array(
        '123.125.',
        '220.181.',
        '121.14.',
        '203.208.',
        '210.72.',
        '125.90.',
        '218.0.',
        '216.239.',
        '64.233.',
        '66.102.',
        '66.249.',
        '72.14.',
        '202.101.',
        '222.73.',
        '66.249.65.',
        '101.226.',
        '180.153.',
        '180.163.',
        '182.118.',
        '61.55.',
        '101.',
        '123.126.',
        '218.30.',
        '61.135.',
        '42.156.',
        '42.120.',
        '202.106.',
        '202.108.',
        '222.185.',
        '65.54.',
        '207.46.',
        '207.68.',
        '219.133.',
        '202.96.',
        '202.104.',
        '219.142.',
        '66.196.',
        '68.142.',
        '72.30.',
        '74.6.',
        '202.165.',
        '202.160.',
        '42.236.',
    );
    foreach($spiderSite as $val) {
        if (strpos($ip, $val) === 0) {
            return true;
        }
    }
    return false;
}

//验证码生成图片
function codeImage($code){
    $image_width = strlen($code);
    $size = 10;//字体大小
    $font = "/usr/share/fonts/dejavu/DejaVuSans.ttf";
    $image_width < 7 ? $multiple = 9 : $multiple = 8.3;
    $img = imagecreate($image_width * $multiple, 24);
    imagecolorallocatealpha($img,242, 242, 242, 127);//设置图片背景颜色，这里背景颜色为#ffffff，也就是白色
    $black = imagecolorallocate($img, 0, 0, 0);//设置字体颜色，这里为#000000，也就是黑色
    imagettftext($img, $size,0, 2, 16, $black, $font, $code);
    $filePathDir = \think\facade\Env::get('root_path') . '/public/code/';
    if (!is_dir($filePathDir)) {
        mkdir($filePathDir, 0777, true);
    }

    $filePath   = 'code/' . md5(time().rand(0,9999)).'.png';
    imagepng($img, $filePath);
    return \think\facade\Request::domain() . '/' . $filePath;
}

//验证码生成图片
function phoneImage($bh, $phone, $title){
    $phone = '+' . $bh . ' ' . $phone;
    $image_width = strlen($phone);
    $size = 20;//字体大小
    $font = "/usr/share/fonts/dejavu/DejaVuSans.ttf";
    $multiple = 17.3;
    $img = imagecreate($image_width * $multiple, 40);
    imagecolorallocatealpha($img, 242, 242, 242, 127);//设置图片背景颜色，这里背景颜色为#ffffff，也就是白色
    $black = imagecolorallocate($img, 0, 0, 0);//设置字体颜色，这里为#000000，也就是黑色
    imagettftext($img, $size,0, 2, 30, $black, $font, $phone);
    $filePathDir = \think\facade\Env::get('root_path') . '/public/static/phone/';
    if (!is_dir($filePathDir)) {
        mkdir($filePathDir, 0777, true);
    }

    $filePath   = 'static/phone/' . $title .'.png';
    imagepng($img, $filePath);
    imagedestroy($img);
    return \think\facade\Request::domain() . '/' . $filePath;
}

//验证码生成图片 preg_replace_callback回调方法
function codePregReplaceCallbackFunction($matches){
    //每个小时的第多少分钟显示网站名
    //$trap_time = config('config.common.trap_minutes');
    if ((date('i', time()) % 10) == 0){
        $host = \think\facade\Request::host();
        if ($host == 'www.sms.com'){
            $host = 'www.sms.com';
        }
        if ($host == '*.com'){
            $host = 'www.*.com';
        }
        $url = ' (' . $host . ')';
    }else{
        $url = null;
    }
    return "<img src='".codeImage($matches[0] . $url)."'>";
}

//号码前台加密显示
function phoneEncryption($phone, $type = 'phone'){
    //&#xa008;
    $str = '';
    for ($i = 0; $i < strlen($phone); $i++){
        $str .= '&#xa00' . substr($phone, $i, 1) . ';';
    }
    if ($type == 'phone'){
        return $str;
    }else{
        return '+' . $str;
    }
}

//一个数字，显示成整数舍入模式 51520 显示成50000+
function numberDim($number){
    if (!$number || $number < 100){
        return '...';
    }
    $number = (string)$number;
    $new_number = substr($number, 0, 1);
    $new_number .= str_repeat('0', strlen($number) - 1);
    return $new_number . '+';
}
