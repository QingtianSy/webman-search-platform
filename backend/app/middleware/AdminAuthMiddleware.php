<?php

namespace app\middleware;

use app\service\auth\JwtService;
use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AdminAuthMiddleware implements MiddlewareInterface
{
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
        return $handler($request);
    }
}
