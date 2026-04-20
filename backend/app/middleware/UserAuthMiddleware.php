<?php

namespace app\middleware;

use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;
use app\repository\redis\TokenCacheRepository;
use app\service\auth\JwtService;
use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class UserAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $authorization = (string) $request->header('Authorization', '');
        if ($authorization === '') {
            return ApiResponse::error(40002, '未登录');
        }
        $token = preg_match('/^Bearer\s+(.+)$/i', $authorization, $m) ? $m[1] : '';
        $decoded = (new JwtService())->decode($token);
        if (empty($decoded)) {
            return ApiResponse::error(40002, 'Token 无效');
        }
        $userId = (int) ($decoded['payload']['uid'] ?? 0);
        $tokenCache = new TokenCacheRepository();
        $tokenStatus = $tokenCache->getUserTokenWithStatus($userId);
        $storedToken = $tokenStatus['token'];
        $redisConnected = $tokenStatus['connected'];

        if ($storedToken !== null && $storedToken !== $token) {
            return ApiResponse::error(40002, 'Token 已失效，请重新登录');
        }

        // 注意：此前在 $redisConnected && $storedToken === null 时直接拒绝，这会导致：
        //   1) Redis 重启/LRU 淘汰后所有合法 JWT 被当成吊销；
        //   2) Redis 宕机期间 AuthController 允许 fail-open 发 token，Redis 恢复后这些 token 又被判失效。
        // 吊销由 DB sessions_invalidated_at 权威控制，key 缺失不再视为吊销信号。

        // DB 校验：即便 Redis 命中旧 token（比如 REVOKED 写失败漏网），
        // 依然用 users.sessions_invalidated_at(DATETIME(3)) 对照 JWT iat_ms 把已吊销 token 拦下。
        // 只有显式吊销动作（密码修改/禁用/角色变更/删号）才会写这一列；昵称、头像、邮箱等资料更新不写。
        $user = (new UserRepository())->findById($userId);
        if (!$user || (int) ($user['status'] ?? 0) !== 1) {
            return ApiResponse::error(40002, '用户不存在或已被禁用');
        }
        $invalidatedMs = JwtService::datetimeToMs($user['sessions_invalidated_at'] ?? null);
        if ($invalidatedMs > 0) {
            $iatMs = (int) ($decoded['iat_ms'] ?? 0);
            if ($iatMs <= 0) {
                // 升级前签发的老 token 没有 iat_ms，退化到秒级兜底比较。
                $iatMs = ((int) ($decoded['iat'] ?? 0)) * 1000;
            }
            if ($iatMs > 0 && $invalidatedMs > $iatMs) {
                return ApiResponse::error(40002, 'Token 已失效，请重新登录');
            }
        }

        $rolesFromDb = null;
        if (!$redisConnected) {
            error_log("[UserAuthMiddleware] Redis unavailable, falling back to DB verification for user {$userId}");
            $roleIds = (new UserRoleRepository())->roleIdsByUserId($userId);
            $rolesFromDb = !empty($roleIds)
                ? (new RolePermissionRepository())->roleCodesByIds($roleIds)
                : [];
        }

        $request->userId = $userId;
        $request->userRoles = $rolesFromDb ?? ($decoded['payload']['roles'] ?? []);
        return $handler($request);
    }
}
