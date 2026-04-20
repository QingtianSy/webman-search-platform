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
        $storedToken = (new TokenCacheRepository())->getUserToken($userId);
        if ($storedToken !== null && $storedToken !== $token) {
            return ApiResponse::error(40002, 'Token 已失效，请重新登录');
        }

        $rolesFromDb = null;
        if ($storedToken === null) {
            error_log("[UserAuthMiddleware] Redis unavailable, falling back to DB verification for user {$userId}");
            $user = (new UserRepository())->findById($userId);
            if (!$user || (int) ($user['status'] ?? 0) !== 1) {
                return ApiResponse::error(40002, '用户不存在或已被禁用');
            }
            $iat = (int) ($decoded['iat'] ?? 0);
            $updatedAt = strtotime($user['updated_at'] ?? '');
            if ($updatedAt && $iat && $updatedAt > $iat) {
                return ApiResponse::error(40002, 'Token 已失效，请重新登录');
            }
            $roleIds = (new UserRoleRepository())->roleIdsByUserId($userId);
            $rolesFromDb = !empty($roleIds)
                ? (new RolePermissionRepository())->roleCodesByIds($roleIds)
                : [];
            (new TokenCacheRepository())->setUserToken($userId, $token);
        }

        $request->userId = $userId;
        $request->userRoles = $rolesFromDb ?? ($decoded['payload']['roles'] ?? []);
        return $handler($request);
    }
}
