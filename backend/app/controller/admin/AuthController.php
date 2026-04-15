<?php

namespace app\controller\admin;

use support\ApiResponse;

class AuthController
{
    public function login(): array
    {
        return ApiResponse::success([
            'token' => 'todo_admin_jwt_token',
            'expire_at' => 0,
            'admin' => [
                'id' => 0,
                'username' => '',
                'nickname' => '',
                'avatar' => '',
            ],
        ], '管理员登录接口骨架已创建');
    }

    public function profile(): array
    {
        return ApiResponse::success([
            'id' => 0,
            'username' => '',
            'nickname' => '',
            'avatar' => '',
            'status' => 1,
        ]);
    }
}
