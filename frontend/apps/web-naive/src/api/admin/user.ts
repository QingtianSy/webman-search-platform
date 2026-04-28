import { requestClient } from '#/api/request';

export namespace AdminUserApi {
  export interface RoleRef {
    id: number;
    name: string;
    code: string;
  }

  export interface User {
    id: number;
    username: string;
    nickname?: string;
    mobile?: string;
    email?: string;
    status: number;
    avatar?: string;
    balance?: number | string;
    subscription_name?: null | string;
    subscription_expire_at?: null | string;
    subscription_is_unlimited?: null | number;
    subscription_remain_quota?: null | number;
    roles?: RoleRef[];
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    order?: 'asc' | 'desc';
    page?: number;
    page_size?: number;
    sort?: string;
    status?: number | string;
  }

  export interface Page {
    list: User[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    username: string;
    password: string;
    nickname?: string;
    mobile?: string;
    email?: string;
    status?: number;
    role_ids?: number[];
  }

  export interface UpdatePayload {
    id: number;
    username?: string;
    password?: string;
    nickname?: string;
    mobile?: string;
    email?: string;
    status?: number;
    role_ids?: number[];
    balance_delta?: number;
    balance_remark?: string;
    plan_id?: null | number;
    plan_duration_days?: null | number;
  }
}

export async function listUsersApi(params?: AdminUserApi.ListParams) {
  return requestClient.get<AdminUserApi.Page>('/admin/user/list', { params });
}

export async function createUserApi(data: AdminUserApi.CreatePayload) {
  return requestClient.post('/admin/user/create', data);
}

export async function updateUserApi(data: AdminUserApi.UpdatePayload) {
  return requestClient.put('/admin/user/update', data);
}

export async function deleteUserApi(id: number) {
  return requestClient.delete('/admin/user/delete', { params: { id } });
}

export async function toggleUserStatusApi(id: number) {
  return requestClient.put('/admin/user/toggle-status', { id });
}

export async function assignUserRolesApi(user_id: number, role_ids: number[]) {
  return requestClient.put('/admin/user/assign-roles', { user_id, role_ids });
}

export async function resetUserPasswordApi(id: number, new_password: string) {
  return requestClient.put('/admin/user/reset-password', {
    id,
    new_password,
  });
}

export async function adjustUserBalanceApi(
  id: number,
  amount: number,
  remark: string,
) {
  return updateUserApi({
    id,
    balance_delta: amount,
    balance_remark: remark,
  });
}

export async function setUserSubscriptionApi(
  id: number,
  plan_id: null | number,
  plan_duration_days?: null | number,
) {
  return updateUserApi({
    id,
    plan_id,
    plan_duration_days,
  });
}

export async function forceOfflineUserApi(id: number) {
  return requestClient.post('/admin/user/force-offline', { id });
}
