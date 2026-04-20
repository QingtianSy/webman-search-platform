<?php

namespace app\controller\admin;

use app\service\admin\SystemConfigAdminService;
use support\adapter\EpayClient;
use support\ApiResponse;
use support\Request;

class PaymentConfigController
{
    private const ALLOWED_KEYS = [
        'epay_apiurl',
        'epay_pid',
        'epay_sign_type',
        'epay_key',
        'epay_platform_public_key',
        'epay_merchant_private_key',
        'payment_min_amount',
        'payment_max_amount',
    ];

    public function list(Request $request)
    {
        $service = new SystemConfigAdminService();
        $configs = $service->getByGroup('payment');
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
        EpayClient::clearConfigCache();
        return ApiResponse::success($result, '支付配置更新成功');
    }
}
