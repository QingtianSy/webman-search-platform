<?php

namespace app\controller;

use support\ApiResponse;

class HealthController
{
    public function health(): array
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'backend',
        ]);
    }

    public function ready(): array
    {
        return ApiResponse::success([
            'ready' => true,
        ]);
    }
}
