import { requestClient } from '#/api/request';

/** 管理端文档管理（CRUD + 分类维护）。用户端只读接口在 api/user/doc.ts。 */
export namespace AdminDocApi {
  export interface CategoryItem {
    id: number;
    name: string;
    parent_id: number;
    sort: number;
  }

  export interface DocItem {
    id: number;
    title: string;
    category_id: number;
    content?: string;
    status: number;
    updated_at?: string;
  }
}

type PageResult<T> = { items: T[]; total: number };

export async function listAdminDocsApi(params?: { category_id?: number; keyword?: string; page?: number; page_size?: number }) {
  return requestClient.get<PageResult<AdminDocApi.DocItem>>('/admin/docs', { params });
}

export async function createAdminDocApi(data: Partial<AdminDocApi.DocItem>) {
  return requestClient.post<AdminDocApi.DocItem>('/admin/docs', data);
}

export async function updateAdminDocApi(id: number, data: Partial<AdminDocApi.DocItem>) {
  return requestClient.put<AdminDocApi.DocItem>(`/admin/docs/${id}`, data);
}

export async function deleteAdminDocApi(id: number) {
  return requestClient.delete(`/admin/docs/${id}`);
}

export async function listDocCategoriesAdminApi() {
  return requestClient.get<AdminDocApi.CategoryItem[]>('/admin/docs/categories');
}

export async function createDocCategoryApi(data: Partial<AdminDocApi.CategoryItem>) {
  return requestClient.post<AdminDocApi.CategoryItem>('/admin/docs/categories', data);
}
