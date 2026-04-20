<?php

namespace app\controller\admin;

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
        $payload = $authService->adminLogin($username, $password);
        if (!$payload) {
            return ApiResponse::error(40003, '无管理端访问权限');
        }

        $user = $payload['user'];
        // 登录 bump sessions_invalidated_at，让早于本次登录的 token 被中间件拒绝。
        // Redis 缓存 + DB bump 已在 issueSessionToken 内原子执行。
        $token = $authService->issueSessionToken((int) $user['id'], [
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

    public function profile(Request $request)
    {
        $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        if (!$payload) {
            return ApiResponse::error(40002, '未登录或用户不存在');
        }
        return ApiResponse::success($payload);
    }
}
