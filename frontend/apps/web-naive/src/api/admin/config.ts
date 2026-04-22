import { requestClient } from '#/api/request';

/**
 * 管理端系统配置管理。对齐后端 [backend/app/controller/admin/SystemConfigController.php](../../../../../../backend/app/controller/admin/SystemConfigController.php)。
 *   - GET    /admin/system-config/list
 *   - POST   /admin/system-config/update   body: {config_key*, config_value*}
 *
 * - 保留键（epay_*, collect_*, payment_min_amount, payment_max_amount, doc_config）不允许通用入口更新，后端会 40003
 * - 敏感值（epay_key, epay_merchant_private_key, epay_platform_public_key）返回脱敏为 '****'
 * - doc_config.api_key JSON 字段脱敏；前端展示时需按类型解析 value_type: 'string' | 'number' | 'json' | 'boolean'
 */
export namespace AdminSystemConfigApi {
  export interface ConfigItem {
    id: number;
    config_key: string;
    config_value: string;
    value_type?: string;
    description?: string;
    group_name?: string;
    is_sensitive?: number;
    created_at?: string;
    updated_at?: string;
  }
}

export async function listSystemConfigsApi() {
  return requestClient.get<AdminSystemConfigApi.ConfigItem[]>(
    '/admin/system-config/list',
  );
}

export async function updateSystemConfigApi(
  config_key: string,
  config_value: string,
) {
  return requestClient.post('/admin/system-config/update', {
    config_key,
    config_value,
  });
}
