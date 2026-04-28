<?php

namespace app\middleware;

use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\UserRepository;
use app\repository\mysql\UserRoleRepository;
use app\repository\redis\PermissionCacheRepository;
use app\repository\redis\TokenCacheRepository;
use app\repository\redis\UserAuthCacheRepository;
use app\service\auth\JwtService;
use support\ApiResponse;
use support\AppLog;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AdminAuthMiddleware implements MiddlewareInterface
{
    protected const ROUTE_PERMISSION_MAP = [
        '/dashboard'         => 'admin.access',
        '/question'          => 'question.manage',
        '/user'              => 'user.manage',
        '/role'              => 'role.manage',
        '/permission'        => 'role.manage',
        '/menu'              => 'menu.manage',
        '/plan'              => 'plan.manage',
        '/announcement'      => 'announcement.manage',
        '/doc'               => 'doc.manage',
        '/doc-config'        => 'doc.manage',
        '/collect'           => 'collect.manage',
        '/collect-config'    => 'collect.manage',
        '/api-source'        => 'api_source.manage',
        '/system-config'     => 'system.config',
        '/proxy'             => 'system.config',
        '/payment-config'    => 'system.config',
        '/log'               => 'log.view',
        '/monitor'           => 'system.config',
    ];

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

        // 注意：key 缺失不再视为吊销信号，吊销由 DB sessions_invalidated_at 权威控制，
        // 避免 Redis 重启/LRU 淘汰或 Redis 宕机期间 fail-open 发出的 token 在恢复后被误判失效。

        // 鉴权合并缓存：一次 Redis 读拿到 status / invalidated_ms / role_codes，
        // 缓存未命中退回 DB 组装并写回；写 sessions_invalidated_at 的路径必须 bust。
        // DB 故障必须走 50001 出口：非严格版本返 null/[] 会让合法 admin token 被翻成 40002/40003。
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
                AppLog::warn("[AdminAuthMiddleware] auth load infra failure user={$userId}: " . $e->getMessage());
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
                $iatMs = ((int) ($decoded['iat'] ?? 0)) * 1000;
            }
            if ($iatMs > 0 && $invalidatedMs > $iatMs) {
                return ApiResponse::error(40002, 'Token 已失效，请重新登录');
            }
        }

        if (!$redisConnected) {
            AppLog::warn("[AdminAuthMiddleware] Redis unavailable, DB fallback for user {$userId}");
        }

        $roles = $auth['role_codes'] ?? [];
        $request->userId = $userId;
        $request->userRoles = $roles;

        // super_admin 直通；其余角色统一按权限 admin.access 判管理端入口，
        // 与登录/门户判定（AuthService::buildAuthPayload 中的 default_portal）保持同一真源，
        // 避免自定义角色已授 admin.access 却被硬编码角色码拒绝。
        if (in_array('super_admin', $roles, true)) {
            return $handler($request);
        }

        $userPermissions = [];
        try {
            $userPermissions = $this->loadPermissions($roles);
        } catch (\RuntimeException $e) {
            AppLog::warn("[AdminAuthMiddleware] permission load infra failure user={$userId}: " . $e->getMessage());
            return ApiResponse::error(50001, '鉴权服务暂不可用，请稍后重试');
        }
        if (!in_array('admin.access', $userPermissions, true)) {
            return ApiResponse::error(40003, '无权限');
        }

        $requiredPermission = $this->resolvePermission($request->path());
        if ($requiredPermission !== null && $requiredPermission !== 'admin.access') {
            if (!in_array($requiredPermission, $userPermissions, true)) {
                return ApiResponse::error(40003, '无权限访问该功能');
            }
        }

        return $handler($request);
    }

    protected function resolvePermission(string $path): ?string
    {
        $sub = str_replace('/api/v1/admin', '', $path);
        foreach (self::ROUTE_PERMISSION_MAP as $prefix => $permission) {
            if ($sub === $prefix || str_starts_with($sub, $prefix . '/')) {
                return $permission;
            }
        }
        return null;
    }

    protected function loadPermissions(array $roleCodes): array
    {
        $cache = new PermissionCacheRepository();
        $cached = $cache->getPermissions($roleCodes);
        if ($cached !== null) {
            return $cached;
        }
        // DB 故障直接向上抛，让主流程返回 50001；
        // 非严格版本会返 [] 导致合法 admin 被误判 40003"无权限"。
        $permissions = (new RolePermissionRepository())->permissionCodesByRoleCodesStrict($roleCodes);
        $cache->setPermissions($roleCodes, $permissions);
        return $permissions;
    }
}
