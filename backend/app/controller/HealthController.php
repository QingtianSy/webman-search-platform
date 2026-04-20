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
        $hasError = !empty(array_filter($detail['services'], fn($s) => $s !== 'ok' && $s !== 'not_configured'));
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
        $required = ['mysql', 'redis'];
        $allOk = true;
        foreach ($detail['services'] as $name => $status) {
            if (in_array($name, $required, true)) {
                if ($status !== 'ok') {
                    $allOk = false;
                    break;
                }
            } else {
                if ($status !== 'ok' && $status !== 'not_configured') {
                    $allOk = false;
                    break;
                }
            }
        }
        $data = ['ready' => $allOk, 'services' => $detail['services']];
        if (!$allOk) {
            return new Response(503, ['Content-Type' => 'application/json'], json_encode(['code' => 0, 'msg' => '', 'data' => $data], JSON_UNESCAPED_UNICODE));
        }
        return ApiResponse::success($data);
    }
}
