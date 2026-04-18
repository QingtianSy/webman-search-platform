<?php

namespace app\middleware;

use support\ApiResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Throwable;

class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        try {
            return $handler($request);
        } catch (Throwable $e) {
            // 记录错误日志
            error_log(sprintf(
                "[%s] %s in %s:%d\nStack trace:\n%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            // 开发环境返回详细错误
            if (config('app.debug', false)) {
                return ApiResponse::error(500, $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]);
            }

            // 生产环境返回通用错误
            return ApiResponse::error(500, '服务器内部错误');
        }
    }
}
