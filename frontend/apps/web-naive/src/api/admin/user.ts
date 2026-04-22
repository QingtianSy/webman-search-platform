import { requestClient } from '#/api/request';

/**
 * 管理端用户管理。对齐后端 [backend/app/controller/admin/UserController.php](../../../../../../backend/app/controller/admin/UserController.php)。
 *   - GET    /admin/user/list?keyword=&status=&page=&page_size=&sort=&order=
 *   - POST   /admin/user/create          body: {username*, password*(>=6), nickname, mobile, email, status, role_ids[]}
 *   - PUT    /admin/user/update          body: {id*, username, nickname, mobile, email, status, password(>=6 if present), role_ids[]}
 *   - DELETE /admin/user/delete?id=
 *   - PUT    /admin/user/toggle-status   body: {id}
 *   - PUT    /admin/user/assign-roles    body: {user_id, role_ids[]}
 *
 * 列表每行返回 roles: [{id,name,code}]，password_hash/password 已在后端 makeHidden 里过滤。
 * 危险行为（改密码 / 禁用 / 改角色）会触发后端 revokeToken：bump sessions_invalidated_at + Redis 标记，原 token 立即失效。
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
