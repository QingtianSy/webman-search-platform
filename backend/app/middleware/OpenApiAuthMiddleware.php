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
        // verify() 新语义：null=凭证无效；RuntimeException=基础设施故障。
        // 分两类响应，避免 DB 故障时合法调用方拿到 40008 "API Key 无效"。
        try {
            $keyInfo = (new ApiKeyService())->verify($apiKey, $apiSecret);
        } catch (\RuntimeException $e) {
            error_log("[OpenApiAuthMiddleware] verify infra failure: " . $e->getMessage());
            return ApiResponse::error(50001, '鉴权服务暂不可用，请稍后重试');
        }
        if ($keyInfo === null) {
            return ApiResponse::error(40008, 'API Key 无效');
        }
        $request->apiKeyUserId = (int) ($keyInfo['user_id'] ?? 0);
        $request->apiKeyId = (int) ($keyInfo['id'] ?? 0);
        return $handler($request);
    }
}
