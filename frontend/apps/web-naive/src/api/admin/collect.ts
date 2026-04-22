import { requestClient } from '#/api/request';

/** 管理端采集任务监控。 */
export namespace AdminCollectApi {
  export interface TaskItem {
    id: number;
    user_id?: number;
    source: string;
    status: number;
    progress?: number;
    message?: string;
    created_at: string;
    finished_at?: string;
  }
}

type PageResult<T> = { items: T[]; total: number };

export async function listAdminCollectTasksApi(params?: { status?: number; source?: string; page?: number; page_size?: number }) {
  return requestClient.get<PageResult<AdminCollectApi.TaskItem>>('/admin/collect/tasks', { params });
}

export async function retryCollectTaskApi(id: number) {
  return requestClient.post(`/admin/collect/tasks/${id}/retry`);
}

export async function cancelCollectTaskApi(id: number) {
  return requestClient.post(`/admin/collect/tasks/${id}/cancel`);
}
