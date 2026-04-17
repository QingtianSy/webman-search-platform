<?php

namespace app\controller\admin;

use app\service\admin\SystemConfigAdminService;
use app\validate\admin\SystemConfigValidate;
use support\ApiResponse;

class SystemConfigController
{
    public function index()
    {
        return ApiResponse::success(
            (new SystemConfigAdminService())->getList([
                'keyword' => '',
                'status' => null,
                'page' => 1,
                'page_size' => 20,
                'sort' => '',
                'order' => 'desc',
                'start_time' => '',
                'end_time' => '',
            ])
        );
    }

    public function update()
    {
        $data = (new SystemConfigValidate())->update(request()->post());
        return ApiResponse::success(
            (new SystemConfigAdminService())->update($data['config_key'], $data['config_value']),
            '系统配置更新骨架已创建'
        );
    }
}
