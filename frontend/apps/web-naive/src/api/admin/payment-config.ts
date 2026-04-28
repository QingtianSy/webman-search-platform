import { requestClient } from '#/api/request';

/**
 * 管理端 · 支付配置。对齐后端 [backend/app/controller/admin/PaymentConfigController.php](../../../../../../backend/app/controller/admin/PaymentConfigController.php)。
 *   - GET  /admin/payment-config/list
 *   - POST /admin/payment-config/update   body: {config_key, config_value}
 *   - POST /admin/payment-config/test-pay  🆕 Phase 2 末尾补
 *
 * ALLOWED_KEYS:
 *   epay_apiurl, epay_alipay_enabled, epay_wxpay_enabled, epay_qqpay_enabled,
 *   epay_pid, epay_sign_type, epay_key,
 *   epay_platform_public_key, epay_merchant_private_key,
 *   payment_min_amount, payment_max_amount
 *
 * MASKED_KEYS（list 接口返回 ****，update 传 **** 会被拒绝）:
 *   epay_key, epay_platform_public_key, epay_merchant_private_key
 */
export namespace AdminPaymentConfigApi {
  export interface Item {
    config_key: string;
    config_value: string;
    config_type?: string;
    description?: string;
    group?: string;
    updated_at?: string;
    masked?: boolean;
  }

  export interface TestPayResult {
    configured: boolean;
    sign_type?: string;
    apiurl_reachable?: boolean;
    http_status?: null | number;
    error?: null | string;
  }
}

export async function listPaymentConfigApi() {
  return requestClient.get<AdminPaymentConfigApi.Item[]>(
    '/admin/payment-config/list',
  );
}

export async function updatePaymentConfigApi(
  config_key: string,
  config_value: string,
) {
  return requestClient.post('/admin/payment-config/update', {
    config_key,
    config_value,
  });
}

export async function testPayAdminApi() {
  return requestClient.post<AdminPaymentConfigApi.TestPayResult>(
    '/admin/payment-config/test-pay',
    {},
  );
}
