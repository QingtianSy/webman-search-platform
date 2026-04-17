<?php

namespace app\controller\admin;

use app\service\admin\DocAdminService;
use app\validate\admin\DocValidate;
use support\ApiResponse;
use support\Request;

class DocManageController
{
    public function articles()
    {
        return ApiResponse::success((new DocAdminService())->getList());
    }

    public function create(Request $request)
    {
        $data = (new DocValidate())->create($request->all());
        return ApiResponse::success((new DocAdminService())->create($data), '文档创建骨架已创建');
    }

    public function update(Request $request)
    {
        $data = (new DocValidate())->update($request->all());
        return ApiResponse::success((new DocAdminService())->update($data['id'], $data), '文档更新骨架已创建');
    }

    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new DocAdminService())->delete($id), '文档删除骨架已创建');
    }
}
