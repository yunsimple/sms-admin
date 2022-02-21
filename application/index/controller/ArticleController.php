<?php

namespace app\index\controller;


use app\common\model\ArticleModel;
use think\facade\Request;

class ArticleController extends BaseController
{
    public function detail(){
        $id = Request::param('id');
        $article_model = new ArticleModel();
        $article = $article_model->getArticleById($id);
        $article_model->changeArticleNumber($article['total_num'], $article['id']);
        return $this->fetch('detail', compact('article'));
    }

    public function index(){
        $article = (new ArticleModel())->listArticle();
        return $this->fetch('index', compact('article'));
    }
}