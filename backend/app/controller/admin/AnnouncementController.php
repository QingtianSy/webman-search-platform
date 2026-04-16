<?php

namespace app\controller\admin;

use support\ApiResponse;
use support\Pagination;
use support\Request;

class AnnouncementController
{
    public function index(): array
    {
        $list = (new \app\repository\mysql\AnnouncementRepository())->latest();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function create(?Request $request = null): array
    {
        $request ??= new Request();
        $created = (new \app\repository\mysql\AnnouncementRepository())->create([
            'title' => (string) $request->input('title', '新公告'),
            'content' => (string) $request->input('content', ''),
            'type' => (string) $request->input('type', 'notice'),
            'status' => 1,
            'publish_at' => date('Y-m-d H:i:s'),
        ]);
        return ApiResponse::success($created, '公告创建骨架已创建');
    }

    public function update(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        $updated = (new \app\repository\mysql\AnnouncementRepository())->update($id, [
            'title' => (string) $request->input('title', ''),
            'content' => (string) $request->input('content', ''),
        ]);
        return ApiResponse::success($updated, '公告更新骨架已创建');
    }

    public function delete(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success(['deleted' => true, 'id' => $id], '公告删除骨架已创建');
    }
}
