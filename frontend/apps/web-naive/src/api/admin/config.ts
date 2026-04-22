import { requestClient } from '#/api/request';

/** 管理端系统配置（键值对）。 */
export namespace AdminConfigApi {
  export interface ConfigItem {
    key: string;
    value: string;
    description?: string;
    updated_at?: string;
  }
}

export async function listSystemConfigsApi() {
  return requestClient.get<AdminConfigApi.ConfigItem[]>('/admin/system-configs');
}

export async function updateSystemConfigApi(key: string, value: string) {
  return requestClient.put(`/admin/system-configs/${encodeURIComponent(key)}`, { value });
}

export async function batchUpdateSystemConfigsApi(items: AdminConfigApi.ConfigItem[]) {
  return requestClient.post('/admin/system-configs/batch', { items });
}
