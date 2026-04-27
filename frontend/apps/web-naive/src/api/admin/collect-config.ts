import { requestClient } from '#/api/request';

/**
 * 管理端 · 采集配置。对齐后端 [backend/app/controller/admin/CollectConfigController.php](../../../../../../backend/app/controller/admin/CollectConfigController.php)。
 *   - GET  /admin/collect-config/list
 *   - POST /admin/collect-config/update  body: {config_key, config_value}
 *
 * ALLOWED_KEYS:
 *   collect_concurrency, collect_course_concurrency,
 *   collect_request_interval_ms, collect_separator, collect_output_mode,
 *   collect_timeout_seconds, collect_rate_backoff_ms, collect_rate_recovery_count,
 *   collect_login_max_attempts, collect_progress_interval,
 *   collect_proxy_cooldown_min, collect_proxy_enabled
 */
export namespace AdminCollectConfigApi {
  export interface Item {
    config_key: string;
    config_value: string;
    config_type?: string;
    description?: string;
    group?: string;
    updated_at?: string;
  }
}

export async function listCollectConfigApi() {
  return requestClient.get<AdminCollectConfigApi.Item[]>(
    '/admin/collect-config/list',
  );
}

export async function updateCollectConfigApi(
  config_key: string,
  config_value: string,
) {
  return requestClient.post('/admin/collect-config/update', {
    config_key,
    config_value,
  });
}
