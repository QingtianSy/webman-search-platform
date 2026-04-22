import { requestClient } from '#/api/request';

/** 管理端角色/权限映射。 */
export namespace AdminRoleApi {
  export interface RoleItem {
    id: number;
    name: string;
    code: string;
    sort: number;
    status: number;
    permissions?: string[];
  }

  export interface PermissionItem {
    id: number;
    name: string;
    code: string;
    type: number;
    status?: number;
  }
}

export async function listRolesApi() {
  return requestClient.get<AdminRoleApi.RoleItem[]>('/admin/roles');
}

export async function createRoleApi(data: Partial<AdminRoleApi.RoleItem>) {
  return requestClient.post<AdminRoleApi.RoleItem>('/admin/roles', data);
}

export async function updateRoleApi(id: number, data: Partial<AdminRoleApi.RoleItem>) {
  return requestClient.put<AdminRoleApi.RoleItem>(`/admin/roles/${id}`, data);
}

export async function deleteRoleApi(id: number) {
  return requestClient.delete(`/admin/roles/${id}`);
}

export async function setRolePermissionsApi(id: number, permission_ids: number[]) {
  return requestClient.post(`/admin/roles/${id}/permissions`, { permission_ids });
}

export async function listPermissionsApi() {
  return requestClient.get<AdminRoleApi.PermissionItem[]>('/admin/permissions');
}
