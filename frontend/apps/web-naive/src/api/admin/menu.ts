import { requestClient } from '#/api/request';

/** 管理端菜单维护。 */
export namespace AdminMenuApi {
  export interface MenuItem {
    id: number;
    parent_id: number;
    name: string;
    path: string;
    permission_code: string;
    sort: number;
    status: number;
  }
}

export async function listAllMenusApi() {
  return requestClient.get<AdminMenuApi.MenuItem[]>('/admin/menus');
}

export async function createMenuApi(data: Partial<AdminMenuApi.MenuItem>) {
  return requestClient.post<AdminMenuApi.MenuItem>('/admin/menus', data);
}

export async function updateMenuApi(id: number, data: Partial<AdminMenuApi.MenuItem>) {
  return requestClient.put<AdminMenuApi.MenuItem>(`/admin/menus/${id}`, data);
}

export async function deleteMenuApi(id: number) {
  return requestClient.delete(`/admin/menus/${id}`);
}
