<?php

namespace app\controller\admin;

use app\repository\mysql\AnnouncementRepository;
use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\UserRepository;
use support\ApiResponse;
use support\Pagination;

class UserController
{
    public function index(): array
    {
        $users = [];
        foreach (['demo_user', 'admin'] as $username) {
            $row = (new UserRepository())->findByUsername($username);
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
        $list = [(new SubscriptionRepository())->findCurrentByUserId(1)];
        return ApiResponse::success(Pagination::format(array_filter($list), count(array_filter($list)), 1, 20));
    }
}

class AnnouncementController
{
    public function index(): array
    {
        $list = (new AnnouncementRepository())->latest();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
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
