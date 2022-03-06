<?php

namespace app\common\model;

use app\common\controller\RedisController;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;
use think\model\concern\SoftDelete;

class PhoneModel extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //关联模型
    public function country(){
        return $this->belongsTo('CountryModel', 'country_id', 'id');
    }
    public function warehouse(){
        return $this->belongsTo('WarehouseModel', 'warehouse_id', 'id');
    }
    //后台首页调用所有号码
    public function adminGetAllPhone($page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->page($page, $limit)
            ->order('online', 'desc')
            ->order('sort', 'desc')
            ->order('country_id', 'asc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }
    
        //后台首页调用所有号码
    public function adminGetNormalPhone($page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->page($page, $limit)
            ->order('online', 'desc')
            ->order('sort', 'desc')
            ->order('country_id', 'asc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }

    //后台搜索号码
    public function adminGetPhone($phone_num){
        $result = self::with(['country', 'warehouse'])
            ->where('phone_num', 'like', '%' . $phone_num . '%')
            ->order('online', 'desc')
            ->select();
        $result = $result->hidden(['update_time','delete_time', 'country.id', 'country.bh', 'country.show', 'warehouse.id', 'warehouse.show', 'country_id', 'warehouse_id']);
        return $result;
    }

    //后台根据仓库搜索号码
    public function searchWarehouse($warehouse, $page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('warehouse_id', '=', $warehouse)
            ->order('online', 'desc')
            ->page($page, $limit)
            ->order('id', 'desc')
            ->order('country_id', 'asc')
            ->select();
        return $result;
    }
    
    //后台根据国家搜索号码
    public function searchCountry($country, $page, $limit){
        $result = self::with(['country', 'warehouse'])
            ->where('country_id', '=', $country)
            ->order('online', 'desc')
            ->page($page, $limit)
            ->order('id', 'desc')
            ->order('sort', 'desc')
            ->select();
        return $result;
    }

    //后台根据仓库搜索号码 总条数
    public function warehouseCount($id){
        $result = self::where('warehouse_id', '=', $id)
            ->count();
        return $result;
    }
    
    //后台根据国家搜索号码 总条数
    public function countryCount($id){
        $result = self::where('country_id', '=', $id)
            ->count();
        return $result;
    }

    //后台批量删除
    public function deleteMany($id){
        $result = self::destroy($id, true);
        return $result;
    }

    //后台调用数据总数
    public function adminGetCountNuber(){
        $result = self::count();
        return $result;
    }

    //前台调用数据总数
    public function getCountNuber(){
        $result = self::where('show', '=', 1)
            ->count();
        return $result;
    }
    //查询离线号码
    public function offlineNumber(){
        $result = self::where('online', '=', 0)
            ->where('show', '=', 1)
            ->count();
        return $result;
    }
    //查询最近一周新添加的号码
    public function monthCreateNuber(){
        $result = self::where('show', '=', 1)
            ->whereTime('create_time', 'month')
            ->count();
        return $result;
    }
    //前台查询所有号码信息
    public function getAllPhoneNum($page = 1, $limit = 21){
        $result = self::with('country')
            ->where('show', '=', 1)
            ->order('sort', 'desc')
            ->order('warehouse_id', 'desc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        return $result;
    }
    //前台按条件查询号码
    public function getPartPhoneNum($region){
        switch ($region){
            case 'dl':
                $result = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/dl/[PAGE].html'
                    ]);
                break;
            case 'gat':
                $result = self::with('country')
                    ->where('country_id', 'between', '7,8')
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/gat/[PAGE].html'
                    ]);
                break;
            case 'gw':
                $result = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('country_id', '<>', 8)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    //->order('warehouse_id', 'desc')
                    ->order('id', 'desc')
                    ->paginate(10, false, [
                        'page'=>input('param.page')?:1,
                        'path'=>Request::domain().'/gw/[PAGE].html'
                    ]);
        }
        return $result;
    }

    //小程序调用API
    public function xcxPartPhoneNum($region, $page = 1, $limit = 21){
        switch ($region){
            case 'dl':
                $result = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
                break;
            case 'gat':
                $result = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
                break;
            case 'gw':
                $result = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('sort', 'desc')
                    ->order('id', 'desc')
                    ->page($page, $limit)
                    ->select();
        }
        return $result;
    }

    //查询各地区号码总数
    public function getRegionNum(){
        $region['dl'] = self::with('country')
            ->where('country_id', '=', 1)
            ->where('show', '=', 1)
            ->count();
        $region['gat'] = self::with('country')
            ->where('country_id', '=', 7)
            ->where('show', '=', 1)
            ->count();
        $region['gw'] = self::with('country')
            ->where('country_id', '<>', 1)
            ->where('country_id', '<>', 7)
            ->where('show', '=', 1)
            ->count();
        return $region;
    }

    //查询各地区号码总数,上一个方法的集合
    public function getRegionNumberCount($region = 'all'){
        switch ($region){
            case 'dl':
                $region_count = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->count();
            break;
            case 'gat':
                $region_count = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->count();
                break;
            case 'gw':
                $region_count = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->count();
                break;
            default:
                $region_count['dl'] = self::with('country')
                    ->where('country_id', '=', 1)
                    ->where('show', '=', 1)
                    ->count();
                $region_count['gat'] = self::with('country')
                    ->where('country_id', '=', 7)
                    ->where('show', '=', 1)
                    ->count();
                $region_count['gw'] = self::with('country')
                    ->where('country_id', '<>', 1)
                    ->where('country_id', '<>', 7)
                    ->where('show', '=', 1)
                    ->count();
        }
            return $region_count;
    }

    //查询单条号码信息
    public function getPhoneNum($phone_num){
        $result = self::with(['country', 'warehouse'])
            ->where('phone_num', '=', $phone_num)
            ->where('show', '=', 1)
            ->find();
        return $result;
    }
    
    //查询单条号码信息
    public function getUidDetail($uid){
        $result = self::with(['country', 'warehouse'])
            ->where('uid', '=', $uid)
            ->where('show', '=', 1)
            ->find();
        return $result;
    }
    
    //前台单条号码信息
    public function getPhone($phone_num){
        $result = self::with('country')
            ->where('phone_num', '=', $phone_num)
            ->where('show', '=', 1)
            ->paginate(1);
        return $result;
    }

    //根据号码查询单个数据
    public function getPhoneValue($phone_num, $field){
        $result = self::where('phone_num', '=' , $phone_num)
            ->value($field);
        return $result;
    }

    //根据号码查询单列数据
    public function getPhoneFind($phone_num){
        $result = self::with('country,warehouse')
            ->where('phone_num', '=' , $phone_num)
            ->find();
        return $result;
    }

    //写入号码信息
    public function createPhone($data){
        $result = self::create($data);
        return $result->id;
    }

    //更改是否显示/在线
    public function check01($phone_num, $field, $value){
        $result = self::where('phone_num', '=', $phone_num)
            ->update([$field => $value]);
        return $result;
    }

    //后台批量更新显示 隐藏 在线离线
    public function batchCheck($update){
        $result = self::saveAll($update);
        return $result;
    }
    
    //后台调用 当更换采集服务器后,把所有sort为-8的改为0
    public function batchChangeSort(){
        $result = self::where('sort', '=', -3)
            ->update(['sort' => 0]);
        return $result;
    }
    
        //前台随机获取一个号码显示
    public function getRandom(){
        $result = self::with('country')
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->whereIn('warehouse_id', '26,27')
            ->orderRand()
            ->find();
        return $result;
    }
        /**
     * 后台首页调用在线/离线号码
     * @param int $type 1 在线 2 离线 3 失效 0 所有
     * @return \think\db\Query
     */
    public function getPhoneCount($type = 0){
        $online = self::where('online', '=', 1)->count();
        $offine = self::where('online', '=', 0)->count();
        $lose = self::where('show', '=', 0)->count();
        switch ($type){
            case 0:
                return [
                    'total'=> $online + $offine,
                    'online' => $online,
                    'offline' => $offine,
                    'lose' => $lose
                ];
                break;
            case 1:
                return $online;
                break;
            case 2:
                return $offine;
                break;
            case 3:
                return $lose;
                break;
            case 4:
                return $online + $offine;
        }
    }

    /*根据国家获取号码列表*/
    public function getCountryPhone($country_id = ''){
        $page = Request::param('page');
        if (empty($page)){
            $page = 1;
        }
        $country = Request::param('country');
        if (empty($country)){
            $country = 'all';
        }
        if ($country_id == 'hot'){
            $country = $country_id;
        }

        $sub_domain = get_subdomain();
        $domain = get_domain();
        $cacheKeyCountryPage = 'phonePage:' . $sub_domain . '.' . $domain ."_web_{$country}_" . $page;
        $redis_value = (new RedisController())->redisCheck(Config::get('cache.prefix') . $cacheKeyCountryPage);
        if($redis_value){
            $result = unserialize($redis_value);
        }else{
            $result = $this->_getCountryPhone($country_id, $page, $country);
            if (!$result){
                return $result;
            }
            if (!$result->isEmpty()){
                Cache::tag('phonePage')->set($cacheKeyCountryPage, serialize($result), 1800);
            }
        }
        return $result;
    }

    private function _getCountryPhone($country_id, $page, $country){
        switch ($country_id){
            case []:
                $result = self::with('country')
                    ->where('show', '=', 1)
                    ->where('type', '=', 1)
                    ->where('display', '=', 1)
                    ->order('online', 'desc')
                    ->order('en_sort', 'desc')
                    ->order('id', 'desc')
                    ->paginate(8, false, [
                        'page'=>$page?:1,
                        'path'=>Request::domain()."/phone-number/[PAGE]"
                    ]);
                break;
            case 'upcoming':
                $result = self::with('country')
                    ->where('show', '=', 1)
                    ->where('type', '=', 2)
                    ->order('sort', 'desc')
                    ->paginate(8, false, [
                        'page'=>$page?:1,
                        'path'=>Request::domain()."/".$country."-phone-number/[PAGE]"
                    ]);
                if(count($result) < 1){
                    $result = self::with('country')
                        ->where('show', '=', 1)
                        ->where('type', '=', 1)
                        ->whereTime('create_time', 'month')
                        ->order('id', 'desc')
                        ->paginate(50, false, [
                            'page'=>$page?:1,
                            'path'=>Request::domain()."/".$country."-phone-number/[PAGE]"
                        ]);
                }
                break;
            case 'hot':
                $result = self::with('country')
                    ->where('online', '=', 1)
                    ->where('show', '=', 1)
                    ->where('type', '=', 1)
                    ->where('display', '=', 1)
                    ->order('id', 'desc')
                    ->limit(8)
                    ->select();
                break;
            default:
                $result = self::with('country')
                    ->where('country_id', 'in', $country_id)
                    ->where('display', '=', 1)
                    ->where('type', '=', 1)
                    ->where('show', '=', 1)
                    ->order('online', 'desc')
                    ->order('en_sort', 'desc')
                    ->order('id', 'desc')
                    ->paginate(8, false, [
                        'page'=>$page?:1,
                        'path'=>Request::domain()."/".$country."-phone-number/[PAGE]"
                    ]);
                break;
        }
        return $result;
    }
    
    //sitemap查询所有号码
    public function getAllPhone(){
        $result = self::with(['country', 'warehouse'])
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->order('total_num', 'desc')
            ->select();
        return $result;
    }
    
        //根据仓库查询所有号码
    public function getWarehouseAll($warehouse, $column){
        $result = self::where('warehouse_id', '=' , $warehouse)
            ->where('show', '=', 1)
            ->where('online', '=', 1)
            ->column($column);
        return $result;
    }
    
    /**
     * APP 根据条件获取号码列表
     */
    public function appGetPhone($country_id = null, $page = 1, $limit = 10){
        if (empty($country_id)){
            $result = self::with('country')
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                ->where('online', '=', 1)
                ->order('online', 'desc')
                ->order('en_sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }else{
            $result = self::with('country')
                ->where('country_id', 'in', $country_id)
                ->where('show', '=', 1)
                ->where('type', '=', 1)
                ->where('online', '=', 1)
                ->order('online', 'desc')
                ->order('en_sort', 'desc')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select();
        }
        $phone_data = $result->visible(['id', 'phone_num', 'total_num', 'show', 'country.id', 'country.en_title', 'country.title', 'country.bh']);
        return $phone_data;
    }

    //重构，根据uid查询号码详情，并缓存
    public function getPhoneDetailByUID($uid){
        $phone_detail_key = Config::get('cache.prefix') . 'phone_detail_' . $uid;
        //$result = Cache::get($phone_detail_key);
        $redis = new RedisController('sync');
        $result = $redis->redisCheck($phone_detail_key);
        if ($result){
            return unserialize($result);
        }else{
            $result = self::with(['country', 'warehouse'])
                ->where('uid', '=', $uid)
                ->where('show', '=', 1)
                ->find();
            if (!$result){
                return $result;
            }
            if(!$result->isEmpty()){
                //(new RedisController('master'))->redisSetCache($phone_detail_key, serialize($result->toArray()), 6*3600);
                $redis->redisSetCache($phone_detail_key, serialize($result->toArray()), 6*3600);
            }
        }
        return $result;
    }
    
    /**
     * 根据uid或者phone_num查询对方 缓存
     * @param $value
     * @param string $type
     * @return mixed
     */
    public function getUidPhone($value, $type = 'uid'){
        if ($type == 'uid'){
            $search = 'phone_num';
        }else{
            $search = 'uid';
        }
        return self::where($type, $value)->cache(1800)->value($search);
    }

    /**
     * 重构，根据phone查询号码详情，并缓存
     *
     * @param $type uid id phone
     * @param $value
     * @return PhoneModel|mixed
     */
    public function getPhoneDetail($type, $value){
        if ($type == 'uid'){
            $uid = $type;
        }else{
            //获取uid
        }
        $phone_detail_key = Config::get('cache.prefix') . 'phone_detail_' . $uid;
        //$result = Cache::get($phone_detail_key);
        $redis = new RedisController('sync');
        $result = $redis->redisCheck($phone_detail_key);
        if ($result){
            return unserialize($result);
        }else{
            $result = self::with(['country', 'warehouse'])
                ->where('uid', '=', $uid)
                ->where('show', '=', 1)
                ->find();
            if($result){
                //(new RedisController('master'))->redisSetCache($phone_detail_key, serialize($result->toArray()), 6*3600);
                $redis->redisSetCache($phone_detail_key, serialize($result->toArray()), 6*3600);
            }
        }
        return $result;
    }
    
    public function setPhoneCache($phone = ''){
        $key = env('redis_alias') . ':phone_detail:';
        $redis = new RedisController('master');
        if($phone){
            $result = self::with(['country', 'warehouse'])
                ->where('phone_num', $phone)
                ->find();
            if($result){
                return $redis->set($key . $phone, serialize($result->toArray()));
            }else{
                return false;
            }
        }else{
            //缓存所有
            $phone = self::with(['country', 'warehouse'])->select();
            if(count($phone) > 0){
                $new_data = [];
                foreach ($phone as $value){
                    $new_data[$key . $value['phone_num']] = serialize($value->toArray());
                }
                return $redis->mset($new_data);
            }else{
                return false;
            }
        }
    }

    //查询每个国家的号码总数/不包括隐藏
    public function getCountryPhoneCount($country_id){
        $result = self::where('country_id', $country_id)
            ->where('show', 1)
            ->where('display', 1)
            ->cache(3600)
            ->count();
        return $result;
    }

    //获取upcoming号码数据
    public function getUpcomingNumber(){
        return self::where('display', 1)
            ->where('online', 1)
            ->where('show', 1)
            ->where('type', 2)
            ->cache('upcoming_number',3600)
            ->count();
    }
}