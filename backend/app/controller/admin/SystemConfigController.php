<?php

namespace app\controller\admin;

use app\service\admin\SystemConfigAdminService;
use app\validate\admin\SystemConfigValidate;
use support\ApiResponse;
use support\Request;

class SystemConfigController
{
    public function index()
    {
        return ApiResponse::success((new SystemConfigAdminService())->getList());
    }

    public function update(Request $request)
    {
        $data = (new SystemConfigValidate())->update($request->all());
        return ApiResponse::success((new SystemConfigAdminService())->update($data['config_key'], $data['config_value']), '系统配置更新骨架已创建');
    }
}
