<?php

namespace app\middleware;

use app\repository\redis\RateLimitRepository;
use support\ApiResponse;
use support\AppLog;
use support\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    protected int $maxAttempts;
    protected int $ttl;
    protected string $keyType;

    public function __construct(int $maxAttempts = 60, int $ttl = 60, string $keyType = 'ip')
    {
        $this->maxAttempts = $maxAttempts;
        $this->ttl = $ttl;
        $this->keyType = $keyType;
    }

    public function process(Request $request, callable $handler): Response
    {
        if ($this->keyType === 'user') {
            $identifier = (int) ($request->userId ?? 0);
            $key = "user:{$identifier}:" . $request->path();
        } else {
            $identifier = $request->getRealIp();
            $key = "ip:{$identifier}:" . $request->path();
        }

        $result = (new RateLimitRepository())->hit($key, $this->ttl);

        // Redis 不可用时 fail-open：避免把登录/注册/搜索/下单等关键路径全部拦死。
        // 与 AuthController 的 Redis-down 发 token fail-open 策略、以及中间件的 DB 兜底校验保持一致。
        // 风险：Redis 不可用期间限流失效，可能出现短时刷请求，但这是可接受的代价（Redis 故障本就罕见且会被告警发现）。
        if (!$result['available']) {
            AppLog::warn("[RateLimitMiddleware] rate limit backend unavailable (key={$key}), allowing request through");
            return $handler($request);
        }

        if ($result['count'] > $this->maxAttempts) {
            return ApiResponse::error(42900, '请求过于频繁，请稍后再试');
        }

        return $handler($request);
    }
}
