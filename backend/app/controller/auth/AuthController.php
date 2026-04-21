<?php

namespace app\controller\auth;

use app\repository\mysql\LoginLogRepository;
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

        // 登录 bump sessions_invalidated_at，让早于本次登录签发的 token 被中间件拒绝。
        // Redis 缓存 + DB bump 已在 issueSessionToken 内原子执行。
        // 登录成功日志必须等 token 签发后才写：若 issueSessionToken 抛错，客户端拿到的是失败，
        // 此时不应留下一条 status=1 的审计记录。
        $token = $authService->issueSessionToken((int) $user['id'], [
            'uid' => $user['id'],
            'username' => $user['username'],
            'roles' => $payload['roles'],
            'default_portal' => $payload['default_portal'] ?? 'user',
        ]);

        (new LoginLogRepository())->create(['user_id' => $user['id'], 'ip' => $ip, 'user_agent' => $ua, 'status' => 1]);

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

        // register() 已在事务内完成 users/wallets/user_role + sessions_invalidated_at bump，
        // 并在 payload 中返回同一 iat_ms 签发的 token，Redis 缓存为 best-effort。
        // 这样即便 Redis 写失败也不会出现"账号已建但接口回 500"的半成品。
        $user = $payload['user'];
        $token = $payload['token'];

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

    // 登出：中间件已校验并把 userId 挂到 request 上；service 内把 sessions_invalidated_at 推进到 now，
    // 同时清 Redis token 缓存与合并鉴权缓存。幂等——重复调用仍会刷新 invalidated_ms。
    public function logout(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        if ($userId <= 0) {
            // 走到这里说明 UserAuthMiddleware 已放行却没挂 userId，理论不可达；兜底走 40002 而非 500 以便前端清 token。
            return ApiResponse::error(40002, '未登录');
        }
        (new AuthService())->logout($userId);
        return ApiResponse::success(null, '已登出');
    }
}
