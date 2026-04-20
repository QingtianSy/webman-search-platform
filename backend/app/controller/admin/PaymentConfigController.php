<?php

namespace app\controller\admin;

use app\repository\mysql\SystemConfigRepository;
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

        if ($key === 'epay_sign_type' && strtoupper($value) === 'RSA') {
            $rows = (new SystemConfigRepository())->getByGroup('payment');
            $cfgMap = array_column($rows, 'config_value', 'config_key');
            if (empty($cfgMap['epay_merchant_private_key']) || empty($cfgMap['epay_platform_public_key'])) {
                return ApiResponse::error(40001, '切换到 RSA 签名前，请先配置商户私钥和平台公钥');
            }
        }

        $result = (new SystemConfigAdminService())->update($key, $value);
        EpayClient::clearConfigCache();
        return ApiResponse::success($result, '支付配置更新成功');
    }
}
