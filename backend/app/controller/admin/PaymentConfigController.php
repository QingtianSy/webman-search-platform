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

    // 这几个字段 list() 返回时会被 SystemConfigAdminService::maskRow 改成 head****tail / ****，
    // 如果管理员只是打开列表点保存，前端会把脱敏串原样回传，这里必须拦下来，
    // 否则真实密钥会被覆盖成 **** 导致支付链路全挂。
    private const MASKABLE_KEYS = [
        'epay_key',
        'epay_platform_public_key',
        'epay_merchant_private_key',
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

        // 敏感字段且值里带 **** 视为脱敏回写：拒绝保存，避免把真实密钥覆盖成掩码串。
        // 想"不改"就留空/不传；想改就填完整新值。
        if (in_array($key, self::MASKABLE_KEYS, true) && str_contains((string) $value, '****')) {
            return ApiResponse::error(40001, '该字段当前为脱敏展示，请填写完整新值后再保存');
        }

        if ($key === 'epay_sign_type' && strtoupper($value) === 'RSA') {
            // 切 RSA 前要确认密钥已配置。用非严格的 getByGroup 时，DB 故障 → 返回 []
            // → cfgMap 空 → 误报"请先配置密钥"，运维可能会重新粘贴导致原有密钥被覆盖。
            // 改走 Strict，DB 故障直接抛出，让运维知道是基础设施问题而非密钥缺失。
            try {
                $rows = (new SystemConfigRepository())->getByGroupStrict('payment');
            } catch (\RuntimeException $e) {
                return ApiResponse::error(50001, '配置服务暂不可用，请稍后重试');
            }
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
