<?php

namespace app\middleware;

use app\service\open\ApiKeyService;
use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class OpenApiAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $apiKey = (string) $request->header('x-api-key', '');
        $apiSecret = (string) $request->header('x-api-secret', '');
        $keyInfo = (new ApiKeyService())->verify($apiKey, $apiSecret);
        if ($keyInfo === null) {
            return ApiResponse::error(40008, 'API Key 无效');
        }
        $request->apiKeyUserId = (int) ($keyInfo['user_id'] ?? 0);
        $request->apiKeyId = (int) ($keyInfo['id'] ?? 0);
        return $handler($request);
    }
}
