<?php

namespace app\controller\admin;

use app\repository\redis\TokenCacheRepository;
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
        $token = $jwtService->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
        ]);

        $stored = (new TokenCacheRepository())->setUserToken((int) $user['id'], $token);
        if (!$stored) {
            $redisStatus = (new TokenCacheRepository())->getUserTokenWithStatus((int) $user['id']);
            if ($redisStatus['connected']) {
                return ApiResponse::error(500, '登录服务异常，请稍后重试');
            }
            error_log("[AdminAuthController] setUserToken failed for user {$user['id']}, Redis unavailable — token issued without cache");
        }

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
