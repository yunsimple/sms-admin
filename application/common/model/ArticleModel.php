<?php

namespace app\common\model;


use think\facade\Request;

class ArticleModel extends BaseModel
{
    public function createArticle($data){
        $result = self::allowField(true)->create($data);
        return $result->id;
    }

    public function saveArticle($data){
        $result = self::allowField(true)->update($data);
        return $result->id;
    }

    //前台调用
    public function listArticle(){
        $result = self::where('show', '=', 1)
        	->where('id', 'neq', 21)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->paginate(10);
        return $result;
    }
    
    //多语言调用文章
    public function LangListArticle(){
        $sub_domain = get_subdomain();
        if ($sub_domain == 'www'){
            $sub_domain = 'en';
        }
        if ($sub_domain == 'cn'){
            $sub_domain = 'zh';
        }
        if ($sub_domain == 'tw'){
            $sub_domain = 'zh-TW';
        }
        $result = self::where('show', '=', 1)
            ->where('lang', 'eq', $sub_domain)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->paginate(8, false, [
                'page'=>Request::param('page')?:1,
                'path'=>Request::domain()."/receive-sms-blog/page[PAGE]"
            ]);
        return $result;
    }

    //前台调用单条记录/多语言
    public function getArticleByIdLang($id){
        $sub_domain = get_subdomain();
        if ($sub_domain == 'www'){
            $sub_domain = 'en';
        }
        if ($sub_domain == 'cn'){
            $sub_domain = 'zh';
        }
        if ($sub_domain == 'tw'){
            $sub_domain = 'zh-TW';
        }
        $result = self::where('show', '=', 1)
            ->where('id', '=', $id)
            ->where('lang', '=', $sub_domain)
            ->find();
        return $result;
    }

    //后台管理调用
    public function adminListArticle($page, $limit){
        $result = self::page($page, $limit)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->limit($limit)
            ->select();
        return $result;
    }

    //后台批量删除
    public function deleteMany($id){
        $result = self::destroy($id, true);
        return $result;
    }

    //更改是否显示/在线
    public function check01($article_id, $field, $value){
        $result = self::where('id', '=', $article_id)
            ->update([$field => $value]);
        return $result;
    }

    //前台调用单条记录
    public function getArticleById($id){
        $result = self::where('show', '=', 1)
            ->where('id', '=', $id)
            ->find();
        return $result;
    }

    //前台调用查看次数累加
    public function changeArticleNumber($number, $id){
        $result = self::where('id', '=', $id)
            ->update(['total_num' => $number+1]);
        return $result;
    }

    //后台修改文章
    public function changeArticleById($id, $data){
        $result = self::where('id', '=', $id)
            ->update($data);
        return $result;
    }
    //后台调用单条记录
    public function adminGetArticleById($id){
        $result = self::where('id', '=', $id)
            ->find();
        return $result;
    }
    //后台模糊搜索文章
    public function adminSearchTitle($title){
        $keywords = '%'.$title.'%';
        $result = self::where('title', 'like', $keywords)
            ->select();
        return $result;
    }
    //后台搜索多语言文章
    public function adminSearchGroup($a_id){
        $result = self::where('a_id', '=', $a_id)
            ->select();
        return $result;
    }
}