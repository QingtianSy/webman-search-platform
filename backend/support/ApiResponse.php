<?php

namespace support;

class ApiResponse
{
    public static function success(mixed $data = [], string $msg = 'success', string $requestId = '')
    {
        return json([
            'code' => ResponseCode::SUCCESS,
            'msg' => $msg,
            'data' => $data,
            'request_id' => $requestId,
        ]);
    }

    public static function error(int $code, string $msg = 'error', mixed $data = [], string $requestId = '')
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'request_id' => $requestId,
        ]);
    }
}
