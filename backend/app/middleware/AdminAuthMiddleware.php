<?php

namespace app\middleware;

use app\service\auth\JwtService;
use support\ApiResponse;
use support\Request;

class AdminAuthMiddleware
{
    public function process(?Request $request, callable $handler): mixed
    {
                $authorization = (string) $request->header('Authorization', '');
        if ($authorization === '') {
            return ApiResponse::error(40002, '未登录');
        }
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        if (($decoded['payload']['type'] ?? '') !== 'admin') {
            return ApiResponse::error(40003, '无权限');
        }
        return $handler($request);
    }
}
