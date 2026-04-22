import { requestClient } from '#/api/request';

/**
 * 用户端采集任务。后端由 CollectWorker 进程轮询执行，
 * 前端主要承担：提交任务、列表、详情（轮询 status）。
 */
export namespace CollectApi {
  export interface TaskSubmitParams {
    source: string;
    url?: string;
    keyword?: string;
    [extra: string]: unknown;
  }

  export interface TaskItem {
    id: number;
    source: string;
    status: number;
    progress?: number;
    message?: string;
    created_at: string;
    finished_at?: string;
  }
}

export async function submitCollectTaskApi(data: CollectApi.TaskSubmitParams) {
  return requestClient.post<CollectApi.TaskItem>('/collect/tasks', data);
}

export async function listCollectTasksApi(params?: { page?: number; page_size?: number }) {
  return requestClient.get<{ items: CollectApi.TaskItem[]; total: number }>(
    '/collect/tasks',
    { params },
  );
}

export async function getCollectTaskApi(id: number) {
  return requestClient.get<CollectApi.TaskItem>(`/collect/tasks/${id}`);
}
