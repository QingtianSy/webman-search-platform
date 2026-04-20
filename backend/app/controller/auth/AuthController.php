<?php

namespace app\controller\auth;

use app\repository\mysql\LoginLogRepository;
use app\repository\redis\TokenCacheRepository;
use app\service\auth\AuthService;
use app\service\auth\JwtService;
use app\validate\auth\RegisterValidate;
use support\ApiResponse;
use support\Request;

class AuthController
{
    public function login(Request $request)
    {
        $username = (string) $request->post('username', '');
        $password = (string) $request->post('password', '');
        $ip = $request->getRealIp();
        $ua = (string) $request->header('User-Agent', '');

        $authService = new AuthService();
        $jwtService = new JwtService();
        $payload = $authService->login($username, $password);
        if (!$payload) {
            (new LoginLogRepository())->create(['user_id' => 0, 'ip' => $ip, 'user_agent' => $ua, 'status' => 0]);
            return ApiResponse::error(40002, '账号或密码错误');
        }

        $user = $payload['user'];
        (new LoginLogRepository())->create(['user_id' => $user['id'], 'ip' => $ip, 'user_agent' => $ua, 'status' => 1]);

        $token = $jwtService->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

        $stored = (new TokenCacheRepository())->setUserToken((int) $user['id'], $token);
        if (!$stored) {
            $redisStatus = (new TokenCacheRepository())->getUserTokenWithStatus((int) $user['id']);
            if ($redisStatus['connected']) {
                return ApiResponse::error(500, '登录服务异常，请稍后重试');
            }
            error_log("[AuthController] login setUserToken failed for user {$user['id']}, Redis unavailable — token issued without cache");
        }

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

    public function menus(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        return ApiResponse::success($payload['menus'] ?? []);
    }

    public function permissions(Request $request)
    {
                $authorization = (string) $request->header('Authorization', '');
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        $userId = (int) (($decoded['payload']['uid'] ?? 0));
        $payload = (new AuthService())->profile($userId);
        return ApiResponse::success($payload['permissions'] ?? []);
    }

    public function register(Request $request)
    {
        $data = (new RegisterValidate())->register($request->post());
        $authService = new AuthService();
        $payload = $authService->register($data);

        $user = $payload['user'];
        $token = (new JwtService())->encode([
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

        $stored = (new TokenCacheRepository())->setUserToken((int) $user['id'], $token);
        if (!$stored) {
            $redisStatus = (new TokenCacheRepository())->getUserTokenWithStatus((int) $user['id']);
            if ($redisStatus['connected']) {
                return ApiResponse::error(500, '注册服务异常，请稍后重��');
            }
            error_log("[AuthController] register setUserToken failed for user {$user['id']}, Redis unavailable — token issued without cache");
        }

        return ApiResponse::success([
            'token' => $token,
            'expire_at' => time() + (int) config('jwt.expire', 604800),
            'user' => $user,
            'roles' => $payload['roles'],
            'permissions' => $payload['permissions'],
            'menus' => $payload['menus'],
            'default_portal' => $payload['default_portal'],
        ], '注册成功');
    }

    public function updateProfile(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = array_filter([
            'nickname' => trim((string) $request->post('nickname', '')),
            'email' => trim((string) $request->post('email', '')),
            'mobile' => trim((string) $request->post('phone', '')),
            'avatar' => trim((string) $request->post('avatar', '')),
        ], fn ($v) => $v !== '');

        if (empty($data)) {
            return ApiResponse::error(40001, '没有需要更新的字段');
        }

        $result = (new AuthService())->updateProfile($userId, $data);
        if (!$result) {
            return ApiResponse::error(40002, '更新失败');
        }
        return ApiResponse::success($result, '更新成功');
    }
}
