<?php

namespace app\controller\admin;

use app\service\admin\SystemConfigAdminService;
use support\ApiResponse;
use support\Request;

class CollectConfigController
{
    private const ALLOWED_KEYS = [
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

    public function list(Request $request)
    {
        $configs = (new SystemConfigAdminService())->getByGroup('collect');
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
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            return ApiResponse::error(40001, '不允许修改该配置');
        }
        $result = (new SystemConfigAdminService())->update($key, $value);
        return ApiResponse::success($result, '采集配置更新成功');
    }
}
