<?php

namespace app\simple20161108\controller;


use app\common\controller\RedisController;
use app\common\controller\UpdateController;
use app\common\model\ArticleModel;
use think\facade\Request;
use think\Db;

class ArticleController extends BaseController
{
    public function index(){
        $article_list = (new ArticleModel())->listArticle(3);
        return $this->fetch('index', compact($article_list));
    }

    public function add(){
        return $this->fetch('add');
    }

    public function createArticle(){
        //return Request::param(false);
        $data = Request::param(false);
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['sort'] = 1;
        $content = $data['content'];
        $data['content'] = str_replace('&amp;', '&', $data['content']);
        //dump($data);
        $new_data = [];
        //先把中文文章写入数据库，拿到id
        $a_id = Db::name('article')->insertGetId($data);
        if (!$a_id){
            return show('添加失败,请重试', '', 4000);
        }
        //给源文添加a_id
        $result = Db::name('article')->where('id', $a_id)->update(['a_id'=>$a_id, 'sort'=>1, 'lang'=>'zh']);
        //翻译其他语言
        if (array_key_exists('lang', $data)) {
            $langs = ['en', 'de', 'ja', 'vi', 'ru', 'es', 'fr', 'ar', 'ko', 'zh-TW'];
            //$langs = ['es', 'en'];
            for ($i = 0; $i < count($langs); $i++) {
                $target = $langs[$i];
                $translate = $this->translate($data['title'], $target);
                $new_data[$i]['title'] = $translate;
                //$new_data[$i]['sub_title'] = $data['sub_title'];
                $new_data[$i]['show'] = 0;
                $new_data[$i]['sort'] = $data['sort'];
                //翻译文章内容
                $lang_content = $this->translate($content, $target, '', 'html');
                //内链转换
                $lang_content = $this->articleLinkChange($langs[$i], $lang_content);
                if (!$lang_content){
                    return show('翻译失败，请重试', $target, 4000);
                }
                //$new_data[$i]['content_old'] = $lang_content;
                $new_data[$i]['content'] = $this->clearNbsp($target, $lang_content);
                $new_data[$i]['lang'] = $target;
                $new_data[$i]['create_time'] = time();
                $new_data[$i]['update_time'] = time();
                $new_data[$i]['a_id'] = $a_id;
                //dump($new_data);
            }
            $result = Db::name('article')->insertAll($new_data);
        }
        //array_push($new_data, $data);
        //dump($new_data);
        if ($result > 0) {
            return show('添加成功');
        } else {
            return show('添加失败,请重试', '', 4000);
        }

    }

    //对文章内链进行处理
    protected function articleLinkChange($lang, $article){
        if ($lang == 'zh-TW'){
            //台湾
            return str_replace('www.sms.com', 'tw.sms.com', $article);
        }else{
            //mytempsms
            if ($lang == 'en'){
                $new_article1 = preg_replace('/www.sms.com\"/', 'mytempsms.com"', $article);
                $new_article2 = preg_replace('/www.sms.com\//', 'mytempsms.com/receive-sms-online/', $new_article1);
                return $new_article2;
            }else{
                $new_article1 = preg_replace('/www.sms.com\"/', $lang . '.mytempsms.com"', $article);
                $new_article2 = preg_replace('/www.sms.com\//', $lang . '.mytempsms.com/receive-sms-online/', $new_article1);
                return $new_article2;
            }
        }
        return $article;
    }

    //谷歌翻译后对&nbsp;的处理
    protected function clearNbsp($lang, $lang_content){
        return str_replace('<p>', '<p>&nbsp;&nbsp;&nbsp;&nbsp;', $lang_content);
    }

    /**
     * @param $q 需要翻译的内容
     * @param $target 翻译目标语言
     * @param string $source 本身语言类型
     * @param string $format 翻译类型text  html
     * @return mixed
     */
    protected function translate($q, $target, $source = 'zh-CN', $format = 'text'){
        $params = [
            'q' => $q,
            'source' => $source,
            'format' => $format,
            'target' => $target,
        ];
        $url = 'https://translation.googleapis.com/language/translate/v2?key=AIzaSyDiuMALrmQJpC1yLHjnSZxWegCB_963L-U';
        $translate = json_decode(curl_post($url, $params), true);
        return $translate['data']['translations'][0]['translatedText'];
    }

    //删除

    //上传图片
    public function update(){
        return (new UpdateController())->updateImage();
    }

    //整合layui数据表格式
    public function tableData()
    {
        $data = input('get.');
        $page = $data['page'];
        $limit = $data['limit'];
        $article_model = new ArticleModel();
        //全部显示
        if (count($data) == 2){
            $result = $article_model->adminListArticle($page, $limit);
            $count = count($result);
            $result = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $result,
            ];
            return json($result);
        }
        //搜索
        if (count($data) == 4){
            $title = $data['data']['title'];
            if (is_numeric($title)){
                $data = $article_model->adminSearchGroup($title);
            }else{
                $data = $article_model->adminSearchTitle($title);
            }
            $count = count($data);
            $result = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $data,
            ];
            return json($result);
        }

    }

    //批量删除
    public function deleteMany()
    {
        $data = input('post.data');
        if (!$data) {
            return show('请选择要删除的数据', '', 4000);
        }
        $id = [];
        foreach ($data as $value) {
            array_push($id, $value['id']);
        }
        $result = (new ArticleModel())->deleteMany($id);
        if (!$result) {
            return show('删除失败,请稍候重试', '', 4000);
        } else {
            $redis = new RedisController();
            $redis->delRedis();
            return show('删除成功', $result);
        }
    }

    //开关切换
    public function check01()
    {
        $data = input('post.');
        $article_id = $data['article_id'];
        if ($data['field'] == 'sort') {
            $value = $data['value'];
        } else {
            if ($data['value'] == 0) {
                $value = 1;
            } elseif ($data['value'] == 1) {
                $value = 0;
            }
        }
        switch ($data['field']) {
            case 'show':
                $result = (new ArticleModel())->check01($article_id, 'show', $value);
                break;
            case 'sort':
                $result = (new ArticleModel())->check01($article_id, 'sort', $value);
                break;
            default:
                $result = '';
        }
        if (!$result) {
            return show('切换失败,请稍候重试', '', 4000);
        } else {
//            $redis = new RedisController();
//            $redis->delRedis();
//            $redis->delRedis([1 => 'error_' . $phone_num]);
            return show('修改成功', $result);
        }
    }

    //修改文章
    public function changeArticle(){
        $article = (new ArticleModel())->adminGetArticleById(input('get.id'));
        return $this->fetch('save', compact('article'));
    }
    public function changeArticleById(){
        $data = input('post.');
        $result = (new ArticleModel())->changeArticleById($data['id'], $data);
        if ($result){
            return show('修改成功', $result);
        }else{
            return show('修改失败', '', 4000);
        }
    }
}