<?php

namespace app\controller\admin;

use app\repository\mysql\SystemConfigRepository;
use support\ApiResponse;
use support\Pagination;
use support\InputRequest;

class SystemConfigController
{
    public function index(): array
    {
        $list = (new SystemConfigRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function update(?InputRequest $request = null): array
    {
        $request ??= new InputRequest();
        $key = (string) $request->input('config_key', '');
        $value = (string) $request->input('config_value', '');
        return ApiResponse::success((new SystemConfigRepository())->updateByKey($key, $value), '系统配置更新骨架已创建');
    }
}
