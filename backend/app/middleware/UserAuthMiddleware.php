<?php

namespace app\middleware;

use app\service\auth\JwtService;
use support\ApiResponse;
use support\InputRequest;

class UserAuthMiddleware
{
    public function process(?InputRequest $request, callable $handler): mixed
    {
        $request ??= new InputRequest();
        $authorization = (string) $request->header('Authorization', '');
        if ($authorization === '') {
            return ApiResponse::error(40002, '未登录');
        }
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        if (empty($decoded)) {
            return ApiResponse::error(40002, 'Token 无效');
        }
        return $handler($request);
    }
}
