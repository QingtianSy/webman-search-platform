import { requestClient } from '#/api/request';

/**
 * 管理端采集任务管理。对齐后端 [backend/app/controller/admin/CollectManageController.php](../../../../../../backend/app/controller/admin/CollectManageController.php)。
 *   - GET  /admin/collect/task/list?status=&user_id=&keyword=&page=&page_size=
 *   - GET  /admin/collect/task/detail?task_no=
 *   - POST /admin/collect/task/stop    body: {task_no*}
 *   - POST /admin/collect/task/retry   body: {task_no*}
 *
 * - 任务以 task_no（字符串）为主键；status: 0=待执行 1=执行中 2=成功 3=失败 4=已停止
 * - stop 仅允许 0/1；retry 仅允许 3/4；不匹配会 40003
 * - detail 未命中抛 40004
 */
export namespace AdminCollectApi {
  export interface Task {
    id: number;
    task_no: string;
    user_id?: number;
    account_id?: number;
    account_phone?: string;
    /** courses | course | chapter | exam | homework */
    collect_type?: string;
    course_count?: number;
    question_count?: number;
    success_count?: number;
    fail_count?: number;
    /** 0=待执行 1=执行中 2=成功 3=失败 4=已停止 */
    status: number;
    error_message?: null | string;
    runner_script?: null | string;
    next_script?: null | string;
    created_at: string;
    /** 详情才返 */
    course_ids?: string;
    courses_snapshot?: null | string;
    updated_at?: string;
  }

  export interface ListParams {
    status?: number;
    user_id?: number;
    keyword?: string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Task[];
    total: number;
    page: number;
    page_size: number;
  }
}

export async function listAdminCollectTasksApi(
  params?: AdminCollectApi.ListParams,
) {
  return requestClient.get<AdminCollectApi.Page>('/admin/collect/task/list', {
    params,
  });
}

export async function getAdminCollectTaskDetailApi(task_no: string) {
  return requestClient.get<AdminCollectApi.Task>(
    '/admin/collect/task/detail',
    { params: { task_no } },
  );
}

export async function stopAdminCollectTaskApi(task_no: string) {
  return requestClient.post('/admin/collect/task/stop', { task_no });
}

export async function retryAdminCollectTaskApi(task_no: string) {
  return requestClient.post('/admin/collect/task/retry', { task_no });
}
