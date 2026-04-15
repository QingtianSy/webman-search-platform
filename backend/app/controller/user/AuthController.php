<?php

namespace app\controller\user;

use app\service\auth\AuthService;
use app\service\auth\JwtService;
use support\ApiResponse;
use support\InputRequest;

class AuthController
{
    public function login(?InputRequest $request = null): array
    {
        $request ??= new InputRequest();
        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        $authService = new AuthService();
        $jwtService = new JwtService();
        $payload = $authService->userLogin($username, $password);
        if (!$payload) {
            return ApiResponse::error(40002, '账号或密码错误');
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
            'id' => 1,
            'username' => 'demo_user',
            'nickname' => '测试用户',
            'avatar' => '',
            'status' => 1,
            'roles' => ['user'],
            'permissions' => ['portal.access', 'search.query'],
        ]);
    }
}
