<?php

namespace app\controller;

use app\service\system\HealthService;
use support\ApiResponse;

class HealthController
{
    public function health()
    {
        $service = new HealthService();
        return ApiResponse::success([
            'status' => 'ok',
            'detail' => $service->detail(),
        ]);
    }

    public function ready()
    {
        $service = new HealthService();
        $detail = $service->detail();
        $ready = !in_array(false, $detail['services'], true);
        return ApiResponse::success([
            'ready' => $ready,
            'services' => $detail['services'],
        ]);
    }
}
