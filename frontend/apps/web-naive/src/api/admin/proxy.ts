import { requestClient } from '#/api/request';

/**
 * 管理端 · 代理 IP 池。对齐后端 [backend/app/controller/admin/ProxyController.php](../../../../../../backend/app/controller/admin/ProxyController.php)。
 *   - GET    /admin/proxy/list?keyword=&protocol=&status=&page=&page_size=
 *   - GET    /admin/proxy/detail?id=
 *   - POST   /admin/proxy/create           body: {protocol, host, port, username?, password?, tags?, weight?, status?}
 *   - PUT    /admin/proxy/update?id=       body: partial
 *   - DELETE /admin/proxy/delete?id=
 *   - POST   /admin/proxy/probe?id=
 *   - POST   /admin/proxy/quick-add?raw=   （单条字符串快速新增，如 http://u:p@host:port）
 *   - POST   /admin/proxy/batch-import     body: {items: string[]}   （CSV / 多行解析后批量入库）
 *   - GET    /admin/proxy/batch-export                              （下载 CSV）
 *   - POST   /admin/proxy/probe-all                                 （全量探测，后台排队）
 */
export namespace AdminProxyApi {
  export interface Proxy {
    id: number;
    protocol: 'http' | 'https' | 'socks5' | string;
    host: string;
    port: number;
    username?: string;
    password?: string;
    tags?: string;
    weight?: number;
    status: number;
    success_count?: number;
    fail_count?: number;
    last_tested_at?: null | string;
    last_test_status?: null | string;
    latency_ms?: null | number;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    protocol?: string;
    status?: number | string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Proxy[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    protocol: string;
    host: string;
    port: number;
    username?: string;
    password?: string;
    tags?: string;
    weight?: number;
    status?: number;
  }

  export type UpdatePayload = Partial<CreatePayload>;

  export interface ProbeResult {
    success: boolean;
    latency_ms?: number;
    message?: string;
  }
}

export async function listAdminProxiesApi(params?: AdminProxyApi.ListParams) {
  return requestClient.get<AdminProxyApi.Page>('/admin/proxy/list', { params });
}

export async function getAdminProxyDetailApi(id: number) {
  return requestClient.get<AdminProxyApi.Proxy>('/admin/proxy/detail', {
    params: { id },
  });
}

export async function createAdminProxyApi(data: AdminProxyApi.CreatePayload) {
  return requestClient.post('/admin/proxy/create', data);
}

export async function updateAdminProxyApi(
  id: number,
  data: AdminProxyApi.UpdatePayload,
) {
  return requestClient.put('/admin/proxy/update', data, { params: { id } });
}

export async function deleteAdminProxyApi(id: number) {
  return requestClient.delete('/admin/proxy/delete', { params: { id } });
}

export async function probeAdminProxyApi(id: number) {
  return requestClient.post<AdminProxyApi.ProbeResult>(
    '/admin/proxy/probe',
    null,
    { params: { id } },
  );
}

export async function quickAddAdminProxyApi(raw: string) {
  return requestClient.post('/admin/proxy/quick-add', null, { params: { raw } });
}

export async function batchImportAdminProxyApi(items: string[]) {
  return requestClient.post<{
    success: number;
    failed: number;
    errors?: string[];
  }>('/admin/proxy/batch-import', { items });
}

export async function batchExportAdminProxyApi() {
  return requestClient.get<string>('/admin/proxy/batch-export');
}

export async function probeAllAdminProxyApi() {
  return requestClient.post<{ queued: number }>('/admin/proxy/probe-all');
}
