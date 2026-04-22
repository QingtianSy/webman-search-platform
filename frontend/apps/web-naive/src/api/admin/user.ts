import { requestClient } from '#/api/request';

/**
 * 管理端用户管理。包含 CRUD + 封禁/解封 + 调整余额 + 设置套餐。
 */
export namespace AdminUserApi {
  export interface UserItem {
    id: number;
    username: string;
    nickname?: string;
    status: number;
    default_portal?: string;
    roles?: string[];
    balance?: number;
    current_plan_id?: number | null;
    created_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    status?: number;
    role?: string;
    page?: number;
    page_size?: number;
  }
}

type PageResult<T> = { items: T[]; total: number };

export async function listUsersApi(params?: AdminUserApi.ListParams) {
  return requestClient.get<PageResult<AdminUserApi.UserItem>>('/admin/users', { params });
}

export async function getUserApi(id: number) {
  return requestClient.get<AdminUserApi.UserItem>(`/admin/users/${id}`);
}

export async function createUserApi(data: Partial<AdminUserApi.UserItem> & { password?: string }) {
  return requestClient.post<AdminUserApi.UserItem>('/admin/users', data);
}

export async function updateUserApi(id: number, data: Partial<AdminUserApi.UserItem>) {
  return requestClient.put<AdminUserApi.UserItem>(`/admin/users/${id}`, data);
}

export async function setUserStatusApi(id: number, status: number) {
  return requestClient.post(`/admin/users/${id}/status`, { status });
}

export async function adjustUserBalanceApi(id: number, change: number, reason?: string) {
  return requestClient.post(`/admin/users/${id}/balance`, { change, reason });
}

export async function setUserRolesApi(id: number, role_ids: number[]) {
  return requestClient.post(`/admin/users/${id}/roles`, { role_ids });
}
