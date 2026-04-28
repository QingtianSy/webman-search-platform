import { requestClient } from '#/api/request';

/**
 * 管理端用户管理。对齐后端 [backend/app/controller/admin/UserController.php](../../../../../../backend/app/controller/admin/UserController.php)。
 *   - GET    /admin/user/list?keyword=&status=&page=&page_size=&sort=&order=
 *   - POST   /admin/user/create          body: {username*, password*(>=6), nickname, mobile, email, status, role_ids[]}
 *   - PUT    /admin/user/update          body: {id*, username, nickname, mobile, email, status, password(>=6 if present), role_ids[]}
 *   - DELETE /admin/user/delete?id=
 *   - PUT    /admin/user/toggle-status   body: {id}
 *   - PUT    /admin/user/assign-roles    body: {user_id, role_ids[]}
 *   - PUT    /admin/user/reset-password  🆕 body: {id, new_password}
 *   - PUT    /admin/user/adjust-balance  🆕 body: {id, amount, remark}（+充值/-消费）
 *   - PUT    /admin/user/set-subscription 🆕 body: {id, plan_id}（plan_id=null 清除套餐）
 *   - POST   /admin/user/force-offline   🆕 body: {id}（同步强制下线）
 *
 * 列表每行返回 roles: [{id,name,code}] + balance + subscription_{name,expire_at,is_unlimited,remain_quota}。
 * password_hash/password 已在后端 makeHidden 里过滤。
 * 危险行为（改密码 / 禁用 / 改角色）会触发后端 revokeToken。
 * 编辑套餐（set-subscription）不会吊销 token——仅清 quota cache。
 */
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
    subscription_name?: string | null;
    subscription_expire_at?: string | null;
    subscription_is_unlimited?: number | null;
    subscription_remain_quota?: number | null;
    roles?: RoleRef[];
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    status?: number | string;
    page?: number;
    page_size?: number;
    sort?: string;
    order?: 'asc' | 'desc';
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

// 🆕 管理员重置密码
export async function resetUserPasswordApi(id: number, new_password: string) {
  return requestClient.put('/admin/user/reset-password', {
    id,
    new_password,
  });
}

// 🆕 管理员调整余额（正负）
export async function adjustUserBalanceApi(
  id: number,
  amount: number,
  remark: string,
) {
  return requestClient.put('/admin/user/adjust-balance', {
    id,
    amount,
    remark,
  });
}

// 🆕 设置/清除套餐（plan_id=null 清除）
export async function setUserSubscriptionApi(
  id: number,
  plan_id: number | null,
) {
  return requestClient.put('/admin/user/set-subscription', { id, plan_id });
}

// 🆕 强制下线
export async function forceOfflineUserApi(id: number) {
  return requestClient.post('/admin/user/force-offline', { id });
}
