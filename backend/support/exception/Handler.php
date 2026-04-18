<?php

namespace support\exception;

use Throwable;
use Webman\Http\Request;
use Webman\Http\Response;
use support\ApiResponse;

class Handler
{
    public $dontReport = [];

    public function report(Throwable $exception)
    {
        // 记录错误日志
        error_log(sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }

    public function render(Request $request, Throwable $exception): Response
    {
        // 开发环境返回详细错误
        if (config('app.debug', false)) {
            return ApiResponse::error(500, $exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString())
            ]);
        }

        // 生产环境返回通用错误
        return ApiResponse::error(500, '服务器内部错误');
    }
}
