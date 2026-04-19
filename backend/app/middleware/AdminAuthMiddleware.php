<?php

namespace app\middleware;

use app\repository\mysql\RolePermissionRepository;
use app\repository\redis\PermissionCacheRepository;
use app\service\auth\JwtService;
use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AdminAuthMiddleware implements MiddlewareInterface
{
    protected const ROUTE_PERMISSION_MAP = [
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
    ];

    public function process(Request $request, callable $handler): Response
    {
        $authorization = (string) $request->header('Authorization', '');
        if ($authorization === '') {
            return ApiResponse::error(40002, '未登录');
        }
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        if (empty($decoded)) {
            return ApiResponse::error(40002, 'Token 无效');
        }
        $roles = $decoded['payload']['roles'] ?? [];
        $adminRoles = ['admin', 'super_admin', 'operator'];
        if (empty(array_intersect($roles, $adminRoles)) && ($decoded['payload']['default_portal'] ?? '') !== 'admin') {
            return ApiResponse::error(40003, '无权限');
        }

        $request->userId = (int) ($decoded['payload']['uid'] ?? 0);
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
            if (str_starts_with($sub, $prefix)) {
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
