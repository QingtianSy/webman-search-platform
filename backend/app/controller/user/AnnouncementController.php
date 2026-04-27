<?php

namespace app\controller\user;

use app\model\admin\Announcement;
use support\ApiResponse;
use support\Db;
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
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', date('Y-m-d H:i:s'));
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
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', date('Y-m-d H:i:s'));
            })
            ->first(['id', 'title', 'content', 'type', 'publish_at', 'created_at']);

        if (!$row) {
            return ApiResponse::error(40004, '公告不存在');
        }

        return ApiResponse::success($row->toArray());
    }

    // 标记公告已读：主键 (user_id, announcement_id)，REPLACE 保证幂等。
    // 若 announcement_reads 表尚未迁移（迁移 0023 未执行），降级为 200 空实现，前端红点也能消。
    public function markRead(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (int) $request->post('id', 0);
        if ($userId <= 0) {
            return ApiResponse::error(40002, '未登录');
        }
        if ($id <= 0) {
            return ApiResponse::error(40001, '公告ID不能为空');
        }
        try {
            Db::table('announcement_reads')->updateOrInsert(
                ['user_id' => $userId, 'announcement_id' => $id],
                ['read_at' => date('Y-m-d H:i:s')],
            );
        } catch (\Throwable $e) {
            // 表缺失 / DB 抖动：记录日志，照样返成功，不让前端卡住
            error_log('[AnnouncementController] markRead fallback: ' . $e->getMessage());
        }
        return ApiResponse::success(null, '已标记为已读');
    }
}
