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
        'epay_alipay_enabled',
        'epay_wxpay_enabled',
        'epay_qqpay_enabled',
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

        if (in_array($key, ['epay_alipay_enabled', 'epay_wxpay_enabled', 'epay_qqpay_enabled'], true)) {
            $value = in_array(strtolower((string) $value), ['1', 'true'], true) ? '1' : '0';
        }

        if ($key === 'epay_sign_type') {
            $value = self::normalizeSignType((string) $value);
        }

        if ($key === 'epay_sign_type' && $value === 'v2') {
            // 切 v2 前要确认密钥已配置。用非严格的 getByGroup 时，DB 故障 → 返回 []
            // → cfgMap 空 → 误报"请先配置密钥"，运维可能会重新粘贴导致原有密钥被覆盖。
            // 改走 Strict，DB 故障直接抛出，让运维知道是基础设施问题而非密钥缺失。
            try {
                $rows = (new SystemConfigRepository())->getByGroupStrict('payment');
            } catch (\RuntimeException $e) {
                return ApiResponse::error(50001, '配置服务暂不可用，请稍后重试');
            }
            $cfgMap = array_column($rows, 'config_value', 'config_key');
            if (empty($cfgMap['epay_merchant_private_key']) || empty($cfgMap['epay_platform_public_key'])) {
                return ApiResponse::error(40001, '切换到 v2 签名前，请先配置商户私钥和平台公钥');
            }
        }

        $result = (new SystemConfigAdminService())->update($key, $value);
        EpayClient::clearConfigCache();
        return ApiResponse::success($result, '支付配置更新成功');
    }

    /**
     * 支付配置连通性诊断。不创建真实订单、不动用金额，只校验：
     *   1) 必填项是否齐全（apiurl/pid/key；v2 多校验一对密钥）
     *   2) apiurl 是否 HTTPS 可达（HEAD /mapi.php，超时 5s）
     * 用于管理员保存配置后的"自检按钮"，避免真下单踩雷。
     */
    public function testPay(Request $request)
    {
        EpayClient::clearConfigCache();
        $client = new EpayClient();
        if (!$client->isConfigured()) {
            return ApiResponse::error(40001, '支付配置不完整：请检查 apiurl/pid/key 及 v2 密钥对', [
                'configured' => false,
            ]);
        }

        try {
            $rows = (new SystemConfigRepository())->getByGroupStrict('payment');
        } catch (\RuntimeException $e) {
            return ApiResponse::error(50001, '配置服务暂不可用，请稍后重试');
        }
        $map = array_column($rows, 'config_value', 'config_key');
        $apiurl = rtrim((string) ($map['epay_apiurl'] ?? ''), '/') . '/';
        $signType = self::normalizeSignType((string) ($map['epay_sign_type'] ?? 'v1'));

        $diagnostic = [
            'configured' => true,
            'sign_type' => $signType,
            'apiurl_reachable' => false,
            'http_status' => null,
            'error' => null,
        ];

        // 对端可达性探测：HEAD 根路径；用 curl 直连，避免把 EpayClient 内部 http 改造成可 mock。
        $probeUrl = $apiurl . 'mapi.php';
        $ch = curl_init($probeUrl);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err !== '') {
            $diagnostic['error'] = $err;
            return ApiResponse::error(50001, '支付网关不可达：' . $err, $diagnostic);
        }
        $diagnostic['http_status'] = $http;
        // 2xx/3xx/405（部分网关对 HEAD 拒绝但服务活着）均视为可达
        $diagnostic['apiurl_reachable'] = ($http > 0 && $http < 500);
        if (!$diagnostic['apiurl_reachable']) {
            return ApiResponse::error(50001, "支付网关响应异常：HTTP {$http}", $diagnostic);
        }

        return ApiResponse::success($diagnostic, '支付配置自检通过');
    }

    private static function normalizeSignType(string $value): string
    {
        $value = strtoupper(trim($value));
        return in_array($value, ['V2', 'RSA'], true) ? 'v2' : 'v1';
    }
}
