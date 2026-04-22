import { requestClient } from '#/api/request';

/** 管理端 API 源（第三方题库源）管理。 */
export namespace AdminApiSourceApi {
  export interface SourceItem {
    id: number;
    name: string;
    code: string;
    endpoint?: string;
    status: number;
    priority?: number;
    config?: Record<string, unknown>;
  }
}

export async function listApiSourcesApi() {
  return requestClient.get<AdminApiSourceApi.SourceItem[]>('/admin/api-sources');
}

export async function createApiSourceApi(data: Partial<AdminApiSourceApi.SourceItem>) {
  return requestClient.post<AdminApiSourceApi.SourceItem>('/admin/api-sources', data);
}

export async function updateApiSourceApi(id: number, data: Partial<AdminApiSourceApi.SourceItem>) {
  return requestClient.put<AdminApiSourceApi.SourceItem>(`/admin/api-sources/${id}`, data);
}

export async function deleteApiSourceApi(id: number) {
  return requestClient.delete(`/admin/api-sources/${id}`);
}

export async function testApiSourceApi(id: number, params?: { keyword?: string }) {
  return requestClient.post(`/admin/api-sources/${id}/test`, params);
}
