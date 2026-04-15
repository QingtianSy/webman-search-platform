<?php

namespace support;

/**
 * ApiResponse 兼容占位层。
 *
 * 当前阶段：
 * - 统一返回数组结构，方便 mock / 前后端联调
 *
 * 后续阶段：
 * - 内部逐步对接 Webman JsonResponse / Response 输出
 * - 对外返回结构尽量保持不变
 */
class ApiResponse
{
    public static function success(mixed $data = [], string $msg = 'success', string $requestId = ''): array
    {
        return [
            'code' => ResponseCode::SUCCESS,
            'msg' => $msg,
            'data' => $data,
            'request_id' => $requestId,
        ];
    }

    public static function error(int $code, string $msg = 'error', mixed $data = [], string $requestId = ''): array
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'request_id' => $requestId,
        ];
    }
}
