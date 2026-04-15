<?php

namespace app\controller\open;

use support\ApiResponse;

class HealthController
{
    public function index(): array
    {
        return ApiResponse::success([
            'status' => 'ok',
            'service' => 'open-api',
        ]);
    }
}
