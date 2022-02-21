<?php

namespace app\common\controller;


use think\Controller;
use think\facade\Request;

class UpdateController extends Controller
{
    public function updateImage(){
        // 获取表单上传文件 例如上传了001.jpg
        $files = Request::file();
        //$info = $file->move
        foreach ($files as $file){
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>1567800,'ext'=>'jpg,png,gif'])->move( '../public/uploads/article/');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $data[0] = '/uploads/article/'.$info->getSaveName();
                return json(['errno' => 0, 'msg' => '图片上传成功', 'data' => $data]);
            }else{
                // 上传失败获取错误信息
                return json(['errno' => 1, 'msg' => '图片上传失败', 'data' => $file->getError()]);
            }

        }
    }
}