<?php

namespace support;

class ApiResponse
{
    public static function success(mixed $data = [], string $msg = 'success', int $code = 1)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'request_id' => '',
        ]);
    }

    public static function error(int $code = 500, string $msg = 'error', mixed $data = [])
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'request_id' => '',
        ]);
    }
}
