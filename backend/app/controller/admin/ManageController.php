<?php

namespace app\controller\admin;

use app\repository\mysql\MenuRepository;
use app\repository\mysql\PermissionRepository;
use app\repository\mysql\RoleRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class UserController
{
    public function index(): array
    {
        $users = [];
        foreach (['demo_user', 'admin'] as $username) {
            $row = (new \app\repository\mysql\UserRepository())->findByUsername($username);
            if ($row) {
                unset($row['password']);
                $users[] = $row;
            }
        }
        return ApiResponse::success(Pagination::format($users, count($users), 1, 20));
    }
}

class PlanController
{
    public function index(): array
    {
        $list = [(new \app\repository\mysql\SubscriptionRepository())->findCurrentByUserId(1)];
        return ApiResponse::success(Pagination::format(array_filter($list), count(array_filter($list)), 1, 20));
    }
}

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
}

class SearchLogController
{
    public function index(): array
    {
        $file = dirname(__DIR__, 3) . '/storage/logs/search_logs.jsonl';
        $list = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $list[] = $decoded;
                }
            }
        }
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class RoleController
{
    public function index(): array
    {
        $list = (new RoleRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class PermissionController
{
    public function index(): array
    {
        $list = (new PermissionRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class MenuController
{
    public function index(): array
    {
        $list = (new MenuRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
