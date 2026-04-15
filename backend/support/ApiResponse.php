<?php

namespace support;

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
