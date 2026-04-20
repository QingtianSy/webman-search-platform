<?php

namespace app\controller\admin;

use app\service\admin\SystemConfigAdminService;
use app\validate\admin\AdminQueryValidate;
use app\validate\admin\SystemConfigValidate;
use support\ApiResponse;
use support\Request;

class SystemConfigController
{
    // 这些 key 必须通过各自专用入口修改，否则会绕过白名单/前置校验/运行时缓存失效：
    //   epay_* / payment_min_amount / payment_max_amount → PaymentConfigController（RSA 前置校验 + EpayClient::clearConfigCache）
    //   collect_*                                       → CollectConfigController（白名单）
    //   doc_config                                      → 有 api_key 字段，需走脱敏后的专用展示/更新路径
    // 从通用入口改这些 key 会在 DB 上改成功但运行时仍吃旧缓存，形成"改了但不生效"的状态漂移。
    private const RESERVED_KEYS = [
        'payment_min_amount',
        'payment_max_amount',
        'doc_config',
    ];

    private const RESERVED_PREFIXES = [
        'epay_',
        'collect_',
    ];

    public function index(Request $request)
    {
        $query = (new AdminQueryValidate())->list($request->get());
        return ApiResponse::success((new SystemConfigAdminService())->getList($query));
    }

    public function update(Request $request)
    {
        $data = (new SystemConfigValidate())->update($request->post());
        $key = (string) $data['config_key'];
        if ($this->isReserved($key)) {
            return ApiResponse::error(40001, '该配置项请通过对应专用入口修改');
        }
        return ApiResponse::success(
            (new SystemConfigAdminService())->update($key, $data['config_value']),
            '系统配置更新成功'
        );
    }

    private function isReserved(string $key): bool
    {
        if (in_array($key, self::RESERVED_KEYS, true)) {
            return true;
        }
        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
