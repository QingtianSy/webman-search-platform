<?php

namespace app\controller\user;

use app\model\admin\Announcement;
use support\ApiResponse;
use support\Request;

class AnnouncementController
{
    public function index(Request $request)
    {
        $type = trim((string) $request->get('type', ''));
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = max(1, min(50, (int) $request->get('page_size', 10)));

        $builder = Announcement::query()->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            });
        if (in_array($type, ['notice', 'announcement'], true)) {
            $builder->where('type', $type);
        }

        $total = $builder->count();
        $list = $builder->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get(['id', 'title', 'type', 'publish_at', 'created_at'])
            ->toArray();

        return ApiResponse::success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    public function detail(Request $request)
    {
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '公告ID不能为空');
        }

        $row = Announcement::query()
            ->where('id', $id)
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->first(['id', 'title', 'content', 'type', 'publish_at', 'created_at']);

        if (!$row) {
            return ApiResponse::error(40004, '公告不存在');
        }

        return ApiResponse::success($row->toArray());
    }
}
