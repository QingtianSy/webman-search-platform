<?php

namespace app\controller\admin;

use app\repository\mysql\SystemConfigRepository;
use support\adapter\EpayClient;
use support\ApiResponse;
use support\Request;

class PaymentConfigController
{
    public function list(Request $request)
    {
        $configs = (new SystemConfigRepository())->getByGroup('payment');
        foreach ($configs as &$item) {
            if (in_array($item['config_key'], ['epay_key', 'epay_merchant_private_key', 'epay_platform_public_key'], true)) {
                $val = $item['config_value'] ?? '';
                if (strlen($val) > 8) {
                    $item['config_value'] = substr($val, 0, 4) . '****' . substr($val, -4);
                }
            }
        }
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
            'epay_apiurl',
            'epay_pid',
            'epay_sign_type',
            'epay_key',
            'epay_platform_public_key',
            'epay_merchant_private_key',
            'payment_min_amount',
            'payment_max_amount',
        ];
        if (!in_array($key, $allowedKeys, true)) {
            return ApiResponse::error(40001, '不允许修改该配置');
        }
        if (in_array($key, ['payment_min_amount', 'payment_max_amount'], true) && !is_numeric($value)) {
            return ApiResponse::error(40001, '金额配置须为数值');
        }
        $row = (new SystemConfigRepository())->updateByKey($key, $value);
        if (empty($row)) {
            return ApiResponse::error(40001, '配置项不存在');
        }
        EpayClient::clearConfigCache();
        if (in_array($key, ['epay_key', 'epay_merchant_private_key', 'epay_platform_public_key'], true)) {
            $val = $row['config_value'] ?? '';
            $row['config_value'] = strlen($val) > 8
                ? substr($val, 0, 4) . '****' . substr($val, -4)
                : '****';
        }
        return ApiResponse::success($row);
    }
}
