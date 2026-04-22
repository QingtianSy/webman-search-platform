import { requestClient } from '#/api/request';

/**
 * 管理端角色/权限映射。对齐后端：
 *   - [RoleController.php](../../../../../../backend/app/controller/admin/RoleController.php)
 *   - [PermissionController.php](../../../../../../backend/app/controller/admin/PermissionController.php)
 *
 * 路由：
 *   - GET    /admin/role/list?keyword=&status=&page=&page_size=
 *   - POST   /admin/role/create                   body: {name*, code*, sort, status}
 *   - PUT    /admin/role/update                   body: {id*, name, code, sort, status, permission_ids?}
 *   - DELETE /admin/role/delete?id=
 *   - PUT    /admin/role/assign-permissions       body: {role_id, permission_ids[]}
 *   - GET    /admin/permission/list?keyword=&status=&page=&page_size=
 *
 * 列表每行返回 permissions: [{id,name,code}]。
 * update 里嵌 permission_ids 与独立 assign-permissions 都会触发后端 permission 缓存清零 + 关联用户 token 吊销。
 */
export namespace AdminRoleApi {
  export interface PermissionRef {
    id: number;
    name: string;
    code: string;
  }

  export interface Role {
    id: number;
    name: string;
    code: string;
    sort: number;
    status: number;
    permissions?: PermissionRef[];
    created_at?: string;
    updated_at?: string;
  }

  export interface Permission {
    id: number;
    name: string;
    code: string;
    type?: number;
    status?: number;
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

  export interface Page<T> {
    list: T[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreateRolePayload {
    name: string;
    code: string;
    sort?: number;
    status?: number;
  }

  export interface UpdateRolePayload {
    id: number;
    name?: string;
    code?: string;
    sort?: number;
    status?: number;
    permission_ids?: number[];
  }
}

export async function listRolesApi(params?: AdminRoleApi.ListParams) {
  return requestClient.get<AdminRoleApi.Page<AdminRoleApi.Role>>(
    '/admin/role/list',
    { params },
  );
}

export async function createRoleApi(data: AdminRoleApi.CreateRolePayload) {
  return requestClient.post('/admin/role/create', data);
}

export async function updateRoleApi(data: AdminRoleApi.UpdateRolePayload) {
  return requestClient.put('/admin/role/update', data);
}

export async function deleteRoleApi(id: number) {
  return requestClient.delete('/admin/role/delete', { params: { id } });
}

export async function assignRolePermissionsApi(
  role_id: number,
  permission_ids: number[],
) {
  return requestClient.put('/admin/role/assign-permissions', {
    role_id,
    permission_ids,
  });
}

export async function listPermissionsApi(params?: AdminRoleApi.ListParams) {
  return requestClient.get<AdminRoleApi.Page<AdminRoleApi.Permission>>(
    '/admin/permission/list',
    { params },
  );
}
