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
        $allOk = !empty($detail['services']) && empty(array_filter($detail['services'], fn ($s) => $s !== 'ok' && $s !== 'not_configured'));
        return ApiResponse::success([
            'ready' => $allOk,
            'services' => $detail['services'],
        ]);
    }
}
