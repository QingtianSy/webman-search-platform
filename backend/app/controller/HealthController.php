<?php

namespace app\controller;

use app\service\system\HealthService;
use support\ApiResponse;
use Webman\Http\Response;

class HealthController
{
    public function health()
    {
        $service = new HealthService();
        $detail = $service->detail();
        $hasError = !empty(array_filter($detail['services'], fn($s) => $s === 'error' || $s === 'disconnected'));
        $status = $hasError ? 'degraded' : 'ok';
        return ApiResponse::success([
            'status' => $status,
            'detail' => $detail,
        ]);
    }

    public function ready()
    {
        $service = new HealthService();
        $detail = $service->detail();
        $allOk = !empty($detail['services']) && empty(array_filter($detail['services'], fn ($s) => $s !== 'ok' && $s !== 'not_configured'));
        $data = ['ready' => $allOk, 'services' => $detail['services']];
        if (!$allOk) {
            return new Response(503, ['Content-Type' => 'application/json'], json_encode(['code' => 0, 'msg' => '', 'data' => $data], JSON_UNESCAPED_UNICODE));
        }
        return ApiResponse::success($data);
    }
}
