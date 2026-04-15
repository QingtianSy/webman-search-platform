<?php

namespace app\controller\user;

use support\ApiResponse;

class AuthController
{
    public function login(): array
    {
        return ApiResponse::success([
            'token' => 'todo_jwt_token',
            'expire_at' => 0,
            'user' => [
                'id' => 0,
                'username' => '',
                'nickname' => '',
                'avatar' => '',
            ],
        ], '登录接口骨架已创建');
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
