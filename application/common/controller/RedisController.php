<?php

namespace app\common\controller;


use think\App;
use think\Controller;

/**
 * redis操作类
 * 说明，任何为false的串，存在redis中都是空串。
 * 只有在key不存在时，才会返回false。
 * 这点可用于防止缓存穿透
 */
class RedisController extends Controller
{
    private static $redis;

    //当前数据库ID号
    protected $dbId = 0;

    //当前权限认证码
    protected $auth;

    /**
     * 实例化的对象,单例模式.
     * @var \iphp\db\Redis
     */
    static private $_instance = array();

    private $k;

    //连接属性数组
    protected $attr = array(
        //连接超时时间，redis配置文件中默认为300秒
        'timeout' => 30,
        //选择的数据库。
        'db_id' => 5,
    );

    //什么时候重新建立连接
    protected $expireTime;

    protected $host;

    protected $port;

    protected $config_local = [
        //本地默认连接数据库
        'port' => 6379,
        'host' => '127.0.0.1',
        'auth' => '',
    ];
    protected $config_sync = [
        //本地16699同步数据
        'port' => 17480,
        'host' => '127.0.0.1',
        'auth' => '',
    ];
    protected $config_master = [
        //需要远程同步交流的数据库
        'port' => 16699,
        'host' => '150.158.96.247',
        'auth' => 'r39iO6SE2uMYM5c54Kw9',
    ];


    /**
     * 默认
     * RedisController constructor.
     * @param array $config
     * @param array $attr
     */
    public function __construct($config = "local", $attr = array())
    {
        if ($config == 'local'){
            $config = $this->config_local;
        }elseif($config == 'master'){
            $config = $this->config_master;
        }else{
            $config = $this->config_sync;
        }
        $this->attr = array_merge($this->attr, $attr);
        self::$redis = new \Redis();
        $this->port = $config['port'];
        $this->host = $config['host'];
        self::$redis->connect($this->host, $this->port, $this->attr['timeout']);
        if ($config['auth']) {
            self::$redis->auth($config['auth']);
            $this->auth = $config['auth'];
        }

        $this->expireTime = time() + $this->attr['timeout'];
    }

    /**
     * 得到实例化的对象.
     * 为每个数据库建立一个连接
     * 如果连接超时，将会重新建立一个连接
     * @param array $config
     * @param int $dbId
     * @return \iphp\db\Redis
     */
    public static function getInstance($config, $attr = array())
    {
        //如果是一个字符串，将其认为是数据库的ID号。以简化写法。
        if (!is_array($attr)) {
            $dbId = $attr;
            $attr = array();
            $attr['db_id'] = $dbId;
        }

        $attr['db_id'] = $attr['db_id'] ? $attr['db_id'] : 0;


        $k = md5(implode('', $config) . $attr['db_id']);
        $instance = isset(static::$_instance[$k]) ? static::$_instance[$k] : null;
        if (!($instance instanceof self)) {

            static::$_instance[$k] = new self($config, $attr);
            static::$_instance[$k]->k = $k;
            static::$_instance[$k]->dbId = $attr['db_id'];

            //如果不是0号库，选择一下数据库。
            if ($attr['db_id'] != 0) {
                static::$_instance[$k]->select($attr['db_id']);
            }
        } elseif (time() > static::$_instance[$k]->expireTime) {
            static::$_instance[$k]->close();
            static::$_instance[$k] = new self($config, $attr);
            static::$_instance[$k]->k = $k;
            static::$_instance[$k]->dbId = $attr['db_id'];

            //如果不是0号库，选择一下数据库。
            if ($attr['db_id'] != 0) {
                static::$_instance[$k]->select($attr['db_id']);
            }
        }
        return static::$_instance[$k];
    }

    private function __clone()
    {
    }

    /**
     * 执行原生的redis操作
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * 接口频率限制,如果设定时间超过多少次访问就限制
     * 方法需要优化......
     * @param $title
     * @param $ttl
     * @return bool|string
     */
    public function redisNumber($title, $ttl = 1800)
    {

        //如果键值为ip的redis值不存在就新建
        $re = self::$redis->get($title);
        if (!$re) {
            self::$redis->setex($title, $ttl, 1);
        } else {
            self::$redis->incr($title);
        }
        return self::$redis->get($title);
    }

    public function redisNumberNoTime($title)
    {
        //如果键值为ip的redis值不存在就新建
        $re = self::$redis->get($title);
        if (!$re) {
            self::$redis->set($title, 1);
        } else {
            self::$redis->incr($title);
        }
        return self::$redis->get($title);
    }

    /**
     * 设置string的值.保存永久
     */
    public function setStringValue($key, $value){
        $redis = self::$redis->set($key, $value);
        return $redis;
    }

    /**
     * 接口频率限制,如果设定时间超过多少次访问就限制,以毫秒为单位
     * 方法需要优化......
     * @param $title
     * @param $ttl
     * @return bool|string
     */
    public function redisNumberMill($title, $ttl = 2500)
    {
        $redis = self::$redis;
        //如果键值为ip的redis值不存在就新建
        $re = $redis->get($title);
        if (!$re) {
            $redis->psetex($title, $ttl, 1);
        } elseif($re > 20){
            $redis->del($title);
        }else {
            $redis->incr($title);
        }
        return $redis->get($title);
    }

    //新增string字符串的值
    public function redisSetStringValue($title, $value = '')
    {
        $redis = self::$redis;
        $key = $redis->get($title);
        if (!$key){
            $key = $redis->set($title, $value);
        }
        return $key;
    }
    //根据key值 更改string值
    public function redisChangeStringValue($key, $value){
        $redis = self::$redis;
        $result = $redis->getSet($key, $value);
        return $result;
    }

    //redis缓存首页生成的数据
    public function redisSetCache($title, $data, $ttl = 1800)
    {
        $index_web = self::$redis->get($title);
        if (!$index_web) {
            self::$redis->setex($title, $ttl, $data);
        } else {
            return $index_web;
        }
    }

    //查看值是否存在
    public function redisCheck($title)
    {
        $value = self::$redis->get($title);
        if (!$value) {
            return false;
        } else {
            return $value;
        }
    }

    //清除redis  web和小程序前端缓存json
    public function delRedis($arr = [])
    {
        if (empty($arr)) {
            //self::$redis->del(self::$redis->keys('web*'));
            //self::$redis->del(self::$redis->keys('xcx*'));
            $prefix = config('cache.prefix');
            self::$redis->del(self::$redis->keys($prefix . 'phonePage*'));
/*            self::$redis->del(self::$redis->keys($prefix . 'www.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ru.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ja.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ko.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'es.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'fr.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'de.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ar.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'vi.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'bd.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'it.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ir.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'id.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'pt.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'in.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'my.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'az.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'al.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'pk.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'tr.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'th.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'za.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'mm.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'tz.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'ua.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'pl.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'uz.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'se.my*'));
            self::$redis->del(self::$redis->keys($prefix . 'nl.my*'));*/
        }
        $result = self::$redis->del($arr);
        return $result;
    }

    //清除指定前坠的key
    public function delPrefixRedis($prefix)
    {
        $keys = self::$redis->keys($prefix . '*');
        $result = self::$redis->del($keys);
        return $result;
    }
    //清除单个key
    public function deleteString($key){
        $result = self::$redis->del($key);
        return $result;
    }

    //后台调用缓存redis值
    public function getCacheRedis()
    {
        //获取所有的redis
        $result_web = self::$redis->keys('web*');
        $result_xcx = self::$redis->keys('xcx*');
        $result = array_merge($result_web, $result_xcx);
        return $result;
    }

    //后台根据条件获取以某某为开头的多个redis的键值对
    public function getRedissValue($title)
    {
        $result = self::$redis->keys($title . '*');
        $value = [];
        for ($i = 0; $i < count($result); $i++) {
            $val = $this->redisCheck($result[$i]);
            $value[$result[$i]] = $val;
        }
        return $value;
    }


    /**
     * 采集msg后缓存到redis有序集合里面
     * @param $phone_num
     * @param $data
     * @return array
     */
    public function msgCache($phone_num, $data)
    {
        $number = count($data);
        //dump($data);
        for ($i = 1; $i < $number+1; $i++) {
            self::$redis->zAdd($phone_num, $this->getRedisSet('msg_' . $phone_num . '_score'), serialize($data[$number-$i]));
        }
        //如果集合内数据超过50条,就把该条数据加入队列入库处理
        $number = $this->checkZset($phone_num);
        if ($number > 20) {
            //加入待处理列表
            $this->setSetValue('msg_queue', $phone_num);
        }
        return $this->getMsgCache($phone_num, 20);
    }

    /**
     * 获取Redis string set值没有时间
     */
    public function getRedisSet($title)
    {
        $redis = self::$redis;
        if (!$redis->get($title)) {
            $data = $redis->set($title, 1);
        } else {
            $data = $redis->incr($title);
        }
        return $data;
    }

    /**
     *向集合添加数据
     */
    public function setSetValue($title, $phone_num)
    {
        $result = self::$redis->sAdd($title, $phone_num);
        return $result;
    }

    /*
     * 返回集合所有成员
     */
    public function getSetAllValue($title){
        $result = self::$redis->sMembers($title);
        return $result;
    }
    

    //插入单条数据进入集合
    public function insertZset($phone_num)
    {
        if (!$this->checkZset($phone_num)) {

        }
        self::$redis->zAdd('queue_msg', 1, $phone_num);
    }

    //获取msg缓存数据
    public function getMsgCache($phone_num, $num = 19)
    {
        $result = self::$redis->zRevRange($phone_num, 0, $num);
        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i] = unserialize($result[$i]);
        }
        return $data;
    }

    //判断有序集合是否存在,否则返回条数
    public function checkZset($phone_num)
    {
        $result = self::$redis->zCard($phone_num);
        if ($result > 0) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * zset集合分数排序获取20条之后的数据,仅队列入库使用
     */
    public function getZsetScore($phone_num){
        $max = $this->checkZset($phone_num);
        $result = self::$redis->zRevRange($phone_num, 20, $max);
        //直接返回string存入数据库,要使用时unserialize序列化就行
//        $data = array();
//        for ($i = 0; $i < count($result); $i++) {
//            $data[$i] = unserialize($result[$i]);
//        }
        return $result;
    }

    /**
     * zset移除有序集合中给定的排名区间的所有成员
     */
    public function deleteZset($phone_num){
        $max = $this->checkZset($phone_num);
        $result = self::$redis->zRemRangeByRank($phone_num, 0, $max-21);
        return $result;
    }

    /**
     * set移除集合中一个或多个成员
     */
    public function deleteSet($key, $title){
        $result = self::$redis->sRem($key,$title);
        return $result;
    }

    //删除redis值
    public function delete($title){
        $result = self::$redis->delete($title);
        return $result;
    }

    //删除redis值
    public function del($title){
        $result = self::$redis->del($title);
        return $result;
    }

    //查询是否存在 返回0 1
    public function search($key){
        $result = self::$redis->exists($key);
        return $result;
    }
    
    /**
     * 查看键是否存在，
     * 若 key 存在返回 1 ，否则返回 0 。
     * @param $key
     * @return mixed
     */
    public function exists($key){
        return self::$redis->exists($key);
    }

    //查询是否存在并且返回该值
    public function searchReturnValue($key){
        $result = self::$redis->exists($key);
        if ($result){
            return self::$redis->get($key);
        }else{
            return $result;
        }
    }

    /**
     * 新增优化后 如果值不存在就新增 ，存在就覆盖
     * @param $key
     * @param $value
     * @param int $return 返回方式
     * @param int $ttl
     * @return bool|mixed|string
     */
    public function setStrReturnValue($key, $value, $return = 0, $ttl = 3600){
        $result = self::$redis->setex($key, $ttl, $value);
        if ($result){
            if ($return == 0){
                return true;
            }else{
                return self::$redis->get($key);
            }
        }
    }

    /**
     * APP 根据号码获取redis最新短信
     */
    public function appGetMessage($phone_num, $num = 19)
    {
        $result = self::$redis->zRevRange($phone_num, 0, $num);
        $data = array();
        for ($i = 0; $i < count($result); $i++) {
            $data[$i] = unserialize($result[$i]);
        }
        return $data;
    }


    /**
     * 2020-12-26新增
     * hash 只有在字段 field 不存在时，设置哈希表字段的值。并设置hash失效时间
     * @param $key
     * @param $field
     * @param $value
     * @return bool
     */
    public function hSetNxTtl($key, $field, $value, $ttl = 3600){
        $redis = self::$redis;
        $result = $redis->hSetNx($key, $field, $value);
        if ($result){
            $result = $redis->expire($key, $ttl);
        }
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 不存在新增，存在设置，设置哈希表字段的值。并设置hash失效时间
     * @param $key
     * @param $field
     * @param $value
     * @return bool
     */
    public function hSetTtl($key, $field, $value, $ttl = 3600){
        $redis = self::$redis;
        $result = $redis->hSet($key, $field, $value);
        if ($result){
            $result = $redis->expire($key, $ttl);
        }
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 新增或更改字段的值
     * @param $key
     * @param $field
     * @param $value
     * @return bool|int true新增  false覆盖
     */
    public function hSet($key, $field, $value){
        $result = self::$redis->hSet($key, $field, $value);
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 获取单个字段的值
     * @param $key
     * @param $field
     * @return string
     */
    public function hGet($key, $field){
        $result = self::$redis->hGet($key, $field);
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 获取key的所有字段
     * @param $key
     * @return array
     */
    public function hGetAll($key){
        $result = self::$redis->hGetAll($key);
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 判断hash字段是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    public function hExists($key, $field){
        $result = self::$redis->hExists($key, $field);
        return $result;
    }

    /**
     * 2020-12-26新增
     * hash 一次性获取多个字段的值
     * @param $key
     * @param array $arr
     * @return array
     */
    public function hMget($key, array $arr){
        $result = self::$redis->hMGet($key, $arr);
        return $result;
    }

    /**
     * 2020-12-30新增
     * hash 批量设置多个hash值
     * @param $key
     * @param array $field
     */
    public function hMset($key, array $field){
        $result = self::$redis->hMSet($key, $field);
        return $result;
    }

    /**
     * 2020-12-30新增
     * hash 字段整数自增，不存在将自动生成key
     * @param $key
     * @return int
     */
    public function hIncrby($key, $hashKey, $number = 1){
        $result = self::$redis->hIncrBy($key, $hashKey, $number);
        return $result;
    }

    /**
     * APP用
     * 2021-2-4新增
     * 取有序集合最后一个值
     * @param $key
     * @return array
     */
    public function zLast($key){
        $result = self::$redis->zRange($key, -1, -1);
        return $result;
    }

    /**
     * 5-10新增
     * 判断成员是否存在
     * @param $key 无序集合键
     * @param $value 无序集合成员名称
     */
    public function sIsMember($key, $value){
        $result = self::$redis->sIsMember($key, $value);
        return $result;
    }

    /**
     * -------------------------有序集合-----------------------------
     */

    /**
     * 2021-5-13新增
     * 返回有序集中指定区间内的成员，通过索引，分数从高到低
     */
    public function zRevRange($key, $start, $end){
        $result = self::$redis->zRevRange($key, $start, $end);
        return $result;
    }

    /**
     * 2021-5-13新增
     * 	ZADD key score1 member1 [score2 member2]
     */
    public function zAdd($key, $score, $value){
        $result = self::$redis->zAdd($key, $score, $value);
        return $result;
    }

    /**
     * ------------------------无序集合set---------------------------------
     */

    /**
     * -------------------------字符串string--------------------------------
     */
    /**
     * 设置一个key
     * @param unknown $key
     * @param unknown $value
     */
    public function set($key, $value)
    {
        return self::$redis->set($key, $value);
    }

    /**
     * 得到一个key
     * @param unknown $key
     */
    public function get($key)
    {
        return self::$redis->get($key);
    }

    /**
     * 设置一个有过期时间的key
     * @param unknown $key
     * @param unknown $expire
     * @param unknown $value
     */
    public function setex($key, $expire, $value)
    {
        return self::$redis->setex($key, $expire, $value);
    }


    /**
     * 设置一个key,如果key存在,不做任何操作.
     * @param unknown $key
     * @param unknown $value
     */
    public function setnx($key, $value)
    {
        return self::$redis->setnx($key, $value);
    }

    /**
     * 批量设置key
     * @param unknown $arr
     */
    public function mset($arr)
    {
        return self::$redis->mset($arr);
    }
    
    /**
     * 2021-7-11新增
     * string 字段整数自增，不存在将自动生成key
     * @param $key
     * @return int
     */
    public function incrBy($key, $number = 1){
        $result = self::$redis->incrBy($key, $number);
        return $result;
    }

    //批量获取相同前坠的key
    public function keys($prefix){
        return self::$redis->keys($prefix . '*');
    }
    
    //字符串累加，不存在，新增后累加1，优化，字符串新增
    public function incr($key){
        return self::$redis->incr($key);
    }
    
    //仅当 newkey 不存在时，将 key 改名为 newkey 。
    public function rename($key, $new_key){
        //return $key;
        return self::$redis->rename($key, $new_key);
    }
}