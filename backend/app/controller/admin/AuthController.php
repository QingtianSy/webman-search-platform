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
        $payload = $authService->adminLogin($username, $password);
        if (!$payload) {
            return ApiResponse::error(40003, '无管理端访问权限');
        }

        $user = $payload['user'];
        $token = $jwtService->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
        ]);

        return ApiResponse::success([
            'token' => $token,
            'expire_at' => time() + (int) env('JWT_EXPIRE', 604800),
            'user' => $user,
            'roles' => $payload['roles'],
            'permissions' => $payload['permissions'],
            'menus' => $payload['menus'],
            'default_portal' => $payload['default_portal'],
        ], '登录成功');
    }

    public function profile(): array
    {
        return ApiResponse::success([
            'id' => 2,
            'username' => 'admin',
            'nickname' => '超级管理员',
            'avatar' => '',
            'status' => 1,
            'roles' => ['admin'],
            'permissions' => ['portal.access', 'search.query', 'admin.access', 'question.manage', 'system.config'],
        ]);
    }
}
