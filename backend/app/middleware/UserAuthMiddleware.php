<?php

namespace app\middleware;

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
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        if (empty($decoded)) {
            return ApiResponse::error(40002, 'Token 无效');
        }
        $userId = (int) ($decoded['payload']['uid'] ?? 0);
        $storedToken = (new TokenCacheRepository())->getUserToken($userId);
        if ($storedToken !== null && $storedToken !== $token) {
            return ApiResponse::error(40002, 'Token 已失效，请重新登录');
        }

        $request->userId = $userId;
        $request->userRoles = $decoded['payload']['roles'] ?? [];
        return $handler($request);
    }
}
