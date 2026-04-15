<?php

namespace app\exception;

use Exception;
use support\ApiResponse;
use support\ResponseCode;

class ExceptionHandler
{
    public function render(Exception $exception): array
    {
        if ($exception instanceof BusinessException) {
            return ApiResponse::error($exception->getCode(), $exception->getMessage(), $exception->getData());
        }

        return ApiResponse::error(ResponseCode::SYSTEM_ERROR, $exception->getMessage() ?: '系统异常');
    }
}
