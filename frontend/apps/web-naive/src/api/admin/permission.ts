import { requestClient } from '#/api/request';

/**
 * 管理端权限管理。对齐后端 [backend/app/controller/admin/PermissionController.php](../../../../../../backend/app/controller/admin/PermissionController.php)。
 *   - GET    /admin/permission/list?keyword=&page=&page_size=
 *   - POST   /admin/permission/create   body: {name*, code*, type?, description?, status?}
 *   - PUT    /admin/permission/update   body: {id*, ...partial}
 *   - DELETE /admin/permission/delete?id=
 *
 * - code 全局唯一，用作前端 access 校验 key（与 users.permissions 一一对应）
 * - type: 'menu' | 'action' | 'data'；默认 'action'
 */
export namespace AdminPermissionApi {
  export interface Permission {
    id: number;
    name: string;
    code: string;
    type: string;
    description?: string;
    status: number;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Permission[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    name: string;
    code: string;
    type?: string;
    description?: string;
    status?: number;
  }

  export type UpdatePayload = Partial<CreatePayload> & { id: number };
}

export async function listAdminPermissionsApi(
  params?: AdminPermissionApi.ListParams,
) {
  return requestClient.get<AdminPermissionApi.Page>('/admin/permission/list', {
    params,
  });
}

export async function createPermissionApi(
  data: AdminPermissionApi.CreatePayload,
) {
  return requestClient.post('/admin/permission/create', data);
}

export async function updatePermissionApi(
  data: AdminPermissionApi.UpdatePayload,
) {
  return requestClient.put('/admin/permission/update', data);
}

export async function deletePermissionApi(id: number) {
  return requestClient.delete('/admin/permission/delete', { params: { id } });
}
