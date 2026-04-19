<?php

namespace app\controller\user;

use app\service\auth\AuthService;
use app\service\auth\JwtService;
use support\ApiResponse;
use support\Request;

class AuthController
{
    public function login(Request $request)
    {
        $username = (string) $request->post('username', '');
        $password = (string) $request->post('password', '');

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
            'expire_at' => time() + (int) config('jwt.expire', 604800),
            'user' => $user,
            'roles' => $payload['roles'],
            'permissions' => $payload['permissions'],
            'menus' => $payload['menus'],
            'default_portal' => $payload['default_portal'],
        ], '登录成功');
    }

    public function profile(Request $request)
    {
        $jwtService = new JwtService();
        $token = preg_match('/^Bearer\s+(.+)$/i', (string) $request->header('authorization', ''), $m) ? $m[1] : '';
        $decoded = $jwtService->decode($token);
        if (empty($decoded['payload']['uid'])) {
            return ApiResponse::error(40001, '未登录');
        }
        $authService = new AuthService();
        $profile = $authService->profile((int) $decoded['payload']['uid']);
        if (empty($profile)) {
            return ApiResponse::error(40004, '用户不存在');
        }
        return ApiResponse::success($profile);
    }
}
