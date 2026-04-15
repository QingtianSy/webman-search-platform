<?php

namespace app\controller\admin;

use app\service\auth\AuthService;
use app\service\auth\JwtService;
use support\ApiResponse;
use support\Request;

class AuthController
{
    public function login(?Request $request = null): array
    {
        $request ??= new Request();
        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        $authService = new AuthService();
        $jwtService = new JwtService();
        $admin = $authService->adminLogin($username, $password);
        if (!$admin) {
            return ApiResponse::error(40002, '账号或密码错误');
        }

        $token = $jwtService->encode([
            'uid' => $admin['id'],
            'username' => $admin['username'],
            'type' => 'admin',
        ]);

        return ApiResponse::success([
            'token' => $token,
            'expire_at' => time() + (int) env('JWT_EXPIRE', 604800),
            'admin' => $admin,
        ], '登录成功');
    }

    public function profile(): array
    {
        return ApiResponse::success([
            'id' => 1,
            'username' => 'admin',
            'nickname' => '超级管理员',
            'avatar' => '',
            'status' => 1,
        ]);
    }
}
