<?php

namespace app\controller\admin;

use support\ApiResponse;
use support\Pagination;

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
