<?php

namespace app\controller\admin;

use app\service\admin\AnnouncementAdminService;
use app\validate\admin\AnnouncementValidate;
use support\ApiResponse;
use support\Request;

class AnnouncementController
{
    public function index()
    {
        return ApiResponse::success((new AnnouncementAdminService())->getList());
    }

    public function create(Request $request)
    {
        $data = (new AnnouncementValidate())->create($request->all());
        return ApiResponse::success((new AnnouncementAdminService())->create($data), '公告创建骨架已创建');
    }

    public function update(Request $request)
    {
        $data = (new AnnouncementValidate())->update($request->all());
        return ApiResponse::success((new AnnouncementAdminService())->update($data['id'], $data), '公告更新骨架已创建');
    }

    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new AnnouncementAdminService())->delete($id), '公告删除骨架已创建');
    }
}
