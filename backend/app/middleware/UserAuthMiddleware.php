<?php

namespace app\middleware;

use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;
use app\repository\redis\TokenCacheRepository;
use app\repository\redis\UserAuthCacheRepository;
use app\service\auth\JwtService;
use support\ApiResponse;
use support\AppLog;
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

        // 鉴权合并缓存：status / invalidated_ms / role_codes 一次 Redis 读即可完成，
        // 缓存未命中退回 DB 组装并写回；DB 写 sessions_invalidated_at 的路径必须调用 bust()。
        // DB 故障必须走 50001 出口：非严格版本返 null/[] 会让合法 token 被翻成 40002"用户不存在"。
        $authCache = new UserAuthCacheRepository();
        $auth = $authCache->get($userId);
        if ($auth === null) {
            try {
                $user = (new UserRepository())->findByIdStrict($userId);
                if (!$user) {
                    return ApiResponse::error(40002, '用户不存在或已被禁用');
                }
                $roleIds = (new UserRoleRepository())->roleIdsByUserIdStrict($userId);
                $roleCodes = !empty($roleIds)
                    ? (new RolePermissionRepository())->roleCodesByIdsStrict($roleIds)
                    : [];
            } catch (\RuntimeException $e) {
                AppLog::warn("[UserAuthMiddleware] auth load infra failure user={$userId}: " . $e->getMessage());
                return ApiResponse::error(50001, '鉴权服务暂不可用，请稍后重试');
            }
            $auth = [
                'status' => (int) ($user['status'] ?? 0),
                'invalidated_ms' => JwtService::datetimeToMs($user['sessions_invalidated_at'] ?? null),
                'role_codes' => $roleCodes,
            ];
            if ($redisConnected) {
                $authCache->set($userId, $auth);
            }
        }

        if ((int) ($auth['status'] ?? 0) !== 1) {
            return ApiResponse::error(40002, '用户不存在或已被禁用');
        }

        $invalidatedMs = (int) ($auth['invalidated_ms'] ?? 0);
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

        if (!$redisConnected) {
            AppLog::warn("[UserAuthMiddleware] Redis unavailable, DB fallback for user {$userId}");
        }

        $request->userId = $userId;
        $request->userRoles = $auth['role_codes'] ?? [];
        return $handler($request);
    }
}
