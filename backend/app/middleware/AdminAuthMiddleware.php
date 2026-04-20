<?php

namespace app\middleware;

use app\repository\mysql\RolePermissionRepository;
use app\repository\mysql\UserRepository;
use app\repository\redis\PermissionCacheRepository;
use app\repository\redis\TokenCacheRepository;
use app\service\auth\JwtService;
use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AdminAuthMiddleware implements MiddlewareInterface
{
    protected const ROUTE_PERMISSION_MAP = [
        '/dashboard'         => 'admin.access',
        '/question'          => 'question.manage',
        '/question-category' => 'question.manage',
        '/question-type'     => 'question.manage',
        '/question-source'   => 'question.manage',
        '/question-tag'      => 'question.manage',
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

        // DB 校验：即便 Redis 命中旧 token，也用 users.sessions_invalidated_at(DATETIME(3))
        // 对照 JWT iat_ms 拦截已吊销 token。仅密码/禁用/角色变更/删号写该列。
        $user = (new UserRepository())->findById($userId);
        if (!$user || (int) ($user['status'] ?? 0) !== 1) {
            return ApiResponse::error(40002, '用户不存在或已被禁用');
        }
        $invalidatedMs = JwtService::datetimeToMs($user['sessions_invalidated_at'] ?? null);
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
            error_log("[AdminAuthMiddleware] Redis unavailable, falling back to DB verification for user {$userId}");
        }

        $roleIds = (new \app\repository\mysql\UserRoleRepository())->roleIdsByUserId($userId);
        $roles = !empty($roleIds)
            ? (new RolePermissionRepository())->roleCodesByIds($roleIds)
            : [];

        $request->userId = $userId;
        $request->userRoles = $roles;

        // super_admin 直通；其余角色统一按权限 admin.access 判管理端入口，
        // 与登录/门户判定（AuthService::buildAuthPayload 中的 default_portal）保持同一真源，
        // 避免自定义角色已授 admin.access 却被硬编码角色码拒绝。
        if (in_array('super_admin', $roles, true)) {
            return $handler($request);
        }

        $userPermissions = $this->loadPermissions($roles);
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
        $permissions = (new RolePermissionRepository())->permissionCodesByRoleCodes($roleCodes);
        $cache->setPermissions($roleCodes, $permissions);
        return $permissions;
    }
}
