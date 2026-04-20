<?php

namespace app\middleware;

use app\repository\redis\RateLimitRepository;
use support\ApiResponse;
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

        if (!$result['available'] || $result['count'] > $this->maxAttempts) {
            return ApiResponse::error(42900, '请求过于频繁，请稍后再试');
        }

        return $handler($request);
    }
}
