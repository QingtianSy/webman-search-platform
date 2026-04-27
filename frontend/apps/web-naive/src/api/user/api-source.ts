import { requestClient } from '#/api/request';

/**
 * 题库接口源配置（用户端）。
 * 对齐后端（🆕 待补）：
 *   - GET    /user/api-source/list?name=&status=&page=&page_size=
 *   - GET    /user/api-source/detail?id=
 *   - POST   /user/api-source/create
 *   - POST   /user/api-source/update?id=
 *   - POST   /user/api-source/delete?id=   （支持 ids=1,2,3 批量）
 *   - POST   /user/api-source/test         body: 源配置；返 {ok, status, sample, cost_ms}
 *   - POST   /user/api-source/toggle?id=&status=0|1  快速启停
 */
export namespace ApiSourceApi {
  export interface Source {
    id: number;
    name: string;
    url: string;
    method: 'GET' | 'POST';
    keyword_param: string; // 题干参数名
    type_param?: string | null; // 类型可选参数名
    extra_params?: Record<string, string> | string | null;
    timeout: number; // 秒
    sort?: number;
    status: number; // 0 禁用 / 1 启用
    response_type?: 'form' | 'json' | 'text';
    answer_field?: string;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    name?: string;
    status?: number;
    page?: number;
    page_size?: number;
  }

  export interface ListResult {
    list: Source[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface TestResult {
    ok: boolean;
    status?: number;
    sample?: string;
    cost_ms?: number;
    error?: string;
  }
}

export async function listApiSourcesApi(params: ApiSourceApi.ListParams = {}) {
  try {
    return await requestClient.get<ApiSourceApi.ListResult>(
      '/user/api-source/list',
      { params },
    );
  } catch {
    return {
      list: [],
      total: 0,
      page: 1,
      page_size: params.page_size ?? 10,
    };
  }
}

export async function getApiSourceDetailApi(id: number) {
  return requestClient.get<ApiSourceApi.Source>('/user/api-source/detail', {
    params: { id },
  });
}

export async function createApiSourceApi(
  payload: Omit<ApiSourceApi.Source, 'created_at' | 'id' | 'updated_at'>,
) {
  return requestClient.post<ApiSourceApi.Source>(
    '/user/api-source/create',
    payload,
  );
}

export async function updateApiSourceApi(
  id: number,
  payload: Partial<ApiSourceApi.Source>,
) {
  return requestClient.post<ApiSourceApi.Source>(
    '/user/api-source/update',
    payload,
    { params: { id } },
  );
}

export async function deleteApiSourceApi(ids: number | number[]) {
  const idsParam = Array.isArray(ids) ? ids.join(',') : String(ids);
  return requestClient.post<void>('/user/api-source/delete', null, {
    params: { ids: idsParam },
  });
}

export async function toggleApiSourceApi(id: number, status: number) {
  return requestClient.post<void>('/user/api-source/toggle', null, {
    params: { id, status },
  });
}

export async function testApiSourceApi(
  payload: Partial<ApiSourceApi.Source> & { keyword?: string },
) {
  try {
    return await requestClient.post<ApiSourceApi.TestResult>(
      '/user/api-source/test',
      payload,
    );
  } catch (err) {
    return {
      ok: false,
      error: (err as Error)?.message ?? '测试失败',
    } as ApiSourceApi.TestResult;
  }
}
