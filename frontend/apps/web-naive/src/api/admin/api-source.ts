import { requestClient } from '#/api/request';

/**
 * 管理端 API 源（第三方题库源）管理。对齐后端 [backend/app/controller/admin/ApiSourceManageController.php](../../../../../../backend/app/controller/admin/ApiSourceManageController.php)。
 *   - GET  /admin/api-source/list?page=&page_size=
 *   - GET  /admin/api-source/detail?id=
 *   - POST /admin/api-source/test           body: {id*}  （同步，可能 Guzzle 超时）
 *   - POST /admin/api-source/test-submit    body: {id*}  → {task_id, status:'pending'}
 *   - GET  /admin/api-source/test-result?task_id=
 *
 * - 管理端目前不提供 create/update/delete；源的增删走用户端 /user/api-source/*（普通用户自建源）
 * - 异步测试以 Workerman Timer 投递，scope='admin' 的结果只给 admin 读，避免 task_id 被越权查询
 */
export namespace AdminApiSourceApi {
  export interface Source {
    id: number;
    name: string;
    code?: string;
    endpoint?: string;
    priority?: number;
    status: number;
    success_count?: number;
    fail_count?: number;
    last_tested_at?: string;
    last_test_status?: string;
    config?: Record<string, unknown> | string;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Source[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface TestResult {
    success: boolean;
    action: string;
    id: number;
    data: {
      status: string;
      message?: string;
      tested_at?: string;
      [k: string]: unknown;
    };
  }

  export interface SubmitResult {
    task_id: string;
    status: 'pending';
  }

  export interface TestTaskResult {
    status: 'pending' | 'running' | 'success' | 'failed' | 'error';
    result?: Record<string, unknown>;
    created_at?: string;
    updated_at?: string;
    [k: string]: unknown;
  }
}

export async function listAdminApiSourcesApi(
  params?: AdminApiSourceApi.ListParams,
) {
  return requestClient.get<AdminApiSourceApi.Page>('/admin/api-source/list', {
    params,
  });
}

export async function getAdminApiSourceDetailApi(id: number) {
  return requestClient.get<AdminApiSourceApi.Source>(
    '/admin/api-source/detail',
    { params: { id } },
  );
}

export async function testAdminApiSourceApi(id: number) {
  return requestClient.post<AdminApiSourceApi.TestResult>(
    '/admin/api-source/test',
    { id },
  );
}

export async function submitTestAdminApiSourceApi(id: number) {
  return requestClient.post<AdminApiSourceApi.SubmitResult>(
    '/admin/api-source/test-submit',
    { id },
  );
}

export async function getAdminApiSourceTestResultApi(task_id: string) {
  return requestClient.get<AdminApiSourceApi.TestTaskResult>(
    '/admin/api-source/test-result',
    { params: { task_id } },
  );
}

// 🆕 管理端启禁 API 源（后端 Phase 2 末尾补 /admin/api-source/toggle）
export async function toggleAdminApiSourceApi(id: number) {
  return requestClient.post('/admin/api-source/toggle', { id });
}
