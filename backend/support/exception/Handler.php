<?php

namespace support\exception;

use app\exception\BusinessException;
use Throwable;
use Webman\Exception\ExceptionHandlerInterface;
use Webman\Http\Request;
use Webman\Http\Response;

class Handler implements ExceptionHandlerInterface
{
    public function report(Throwable $exception)
    {
        if ($exception instanceof BusinessException) {
            return;
        }
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
        $requestId = $request->requestId ?? '';

        if ($exception instanceof BusinessException) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'code' => $exception->getCode(),
                'msg' => $exception->getMessage(),
                'data' => $exception->getData(),
                'request_id' => $requestId,
            ], JSON_UNESCAPED_UNICODE));
        }

        if (config('app.debug', false)) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'code' => 500,
                'msg' => $exception->getMessage(),
                'data' => [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString())
                ],
                'request_id' => $requestId,
            ], JSON_UNESCAPED_UNICODE));
        }

        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'code' => 500,
            'msg' => '服务器内部错误',
            'data' => [],
            'request_id' => $requestId,
        ], JSON_UNESCAPED_UNICODE));
    }
}
