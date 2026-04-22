import { requestClient } from '#/api/request';

/**
 * 管理端菜单管理。对齐后端 [backend/app/controller/admin/MenuController.php](../../../../../../backend/app/controller/admin/MenuController.php)。
 *   - GET    /admin/menu/list?keyword=&page=&page_size=
 *   - POST   /admin/menu/create   body: {name*, path*, permission_code*, parent_id?, sort?, status?, icon?, component?}
 *   - PUT    /admin/menu/update   body: {id*, ...partial}
 *   - DELETE /admin/menu/delete?id=
 *
 * - 路由 accessMode='backend' 依赖 GET /auth/menus（按用户角色过滤），此处 list 是全量后台维护视图
 * - parent_id=0 为顶级；component 为前端 views 下相对路径（与 import.meta.glob 匹配）
 */
export namespace AdminMenuApi {
  export interface Menu {
    id: number;
    parent_id: number;
    name: string;
    path: string;
    permission_code: string;
    icon?: string;
    component?: string;
    sort: number;
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
    list: Menu[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    name: string;
    path: string;
    permission_code: string;
    parent_id?: number;
    icon?: string;
    component?: string;
    sort?: number;
    status?: number;
  }

  export type UpdatePayload = Partial<CreatePayload> & { id: number };
}

export async function listAdminMenusApi(params?: AdminMenuApi.ListParams) {
  return requestClient.get<AdminMenuApi.Page>('/admin/menu/list', { params });
}

export async function createMenuApi(data: AdminMenuApi.CreatePayload) {
  return requestClient.post('/admin/menu/create', data);
}

export async function updateMenuApi(data: AdminMenuApi.UpdatePayload) {
  return requestClient.put('/admin/menu/update', data);
}

export async function deleteMenuApi(id: number) {
  return requestClient.delete('/admin/menu/delete', { params: { id } });
}
