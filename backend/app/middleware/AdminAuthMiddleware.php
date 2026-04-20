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

        if ($redisConnected && $storedToken === null) {
            return ApiResponse::error(40002, 'Token 已失效，请重新登录');
        }

        if (!$redisConnected) {
            error_log("[AdminAuthMiddleware] Redis unavailable, falling back to DB verification for user {$userId}");
            $user = (new UserRepository())->findById($userId);
            if (!$user || (int) ($user['status'] ?? 0) !== 1) {
                return ApiResponse::error(40002, '用户不存在或已被禁用');
            }
            $iat = (int) ($decoded['iat'] ?? 0);
            $updatedAt = strtotime($user['updated_at'] ?? '');
            if ($updatedAt && $iat && $updatedAt > $iat) {
                return ApiResponse::error(40002, 'Token 已失效，请重新登录');
            }
        }

        $roleIds = (new \app\repository\mysql\UserRoleRepository())->roleIdsByUserId($userId);
        $roles = !empty($roleIds)
            ? (new RolePermissionRepository())->roleCodesByIds($roleIds)
            : [];

        $adminRoles = ['admin', 'super_admin', 'operator'];
        if (empty(array_intersect($roles, $adminRoles))) {
            return ApiResponse::error(40003, '无权限');
        }

        $request->userId = $userId;
        $request->userRoles = $roles;

        if (in_array('super_admin', $roles, true)) {
            return $handler($request);
        }

        $requiredPermission = $this->resolvePermission($request->path());
        if ($requiredPermission !== null) {
            $userPermissions = $this->loadPermissions($roles);
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
