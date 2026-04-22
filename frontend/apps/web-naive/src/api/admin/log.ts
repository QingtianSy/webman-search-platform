import { requestClient } from '#/api/request';

/** 管理端日志查询（搜索/登录/操作）。 */
export namespace AdminLogApi {
  export interface SearchLog {
    id: number;
    user_id: number;
    username?: string;
    keyword: string;
    result_count?: number;
    ip?: string;
    created_at: string;
  }

  export interface LoginLog {
    id: number;
    user_id: number;
    username?: string;
    ip: string;
    user_agent?: string;
    status: number;
    created_at: string;
  }

  export interface OperationLog {
    id: number;
    user_id: number;
    username?: string;
    module: string;
    action: string;
    target?: string;
    ip?: string;
    created_at: string;
  }
}

type PageResult<T> = { items: T[]; total: number };
type PageParams = { keyword?: string; user_id?: number; page?: number; page_size?: number };

export async function listAdminSearchLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<AdminLogApi.SearchLog>>('/admin/logs/search', { params });
}

export async function listAdminLoginLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<AdminLogApi.LoginLog>>('/admin/logs/login', { params });
}

export async function listAdminOperationLogsApi(params?: PageParams) {
  return requestClient.get<PageResult<AdminLogApi.OperationLog>>('/admin/logs/operation', { params });
}
