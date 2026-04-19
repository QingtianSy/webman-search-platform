<?php

namespace app\controller\admin;

use app\repository\mysql\SystemConfigRepository;
use support\ApiResponse;
use support\Request;

class CollectConfigController
{
    public function list(Request $request)
    {
        $configs = (new SystemConfigRepository())->getByGroup('collect');
        return ApiResponse::success([
            'list' => $configs,
            'total' => count($configs),
        ]);
    }

    public function update(Request $request)
    {
        $key = $request->input('config_key', '');
        $value = $request->input('config_value', '');
        if ($key === '') {
            return ApiResponse::error(40001, '参数错误');
        }
        $allowedKeys = [
            'collect_concurrency',
            'collect_course_concurrency',
            'collect_request_interval_ms',
            'collect_separator',
            'collect_output_mode',
            'collect_timeout_seconds',
            'collect_rate_backoff_ms',
            'collect_rate_recovery_count',
            'collect_login_max_attempts',
            'collect_progress_interval',
            'collect_proxy_cooldown_min',
            'collect_proxy_enabled',
        ];
        if (!in_array($key, $allowedKeys, true)) {
            return ApiResponse::error(40001, '不允许修改该配置');
        }
        $row = (new SystemConfigRepository())->updateByKey($key, $value);
        return ApiResponse::success($row);
    }
}
