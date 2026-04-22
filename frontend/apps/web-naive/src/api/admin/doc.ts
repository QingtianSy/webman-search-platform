import { requestClient } from '#/api/request';

/**
 * 管理端文档管理（CRUD）。对齐后端 [backend/app/controller/admin/DocManageController.php](../../../../../../backend/app/controller/admin/DocManageController.php)。
 *   - GET    /admin/doc/article/list?page=&page_size=
 *   - POST   /admin/doc/article/create   body: {title*, slug*, category_id, summary, content_md, status}
 *   - PUT    /admin/doc/article/update   body: {id*, ...partial}
 *   - DELETE /admin/doc/article/delete?id=
 *
 * - slug 全局唯一，重复返 40001 'duplicate_slug'；page slug 直接用于用户端 /user/doc/article/detail?slug=。
 * - category_id 默认 1；后台暂未提供独立分类管理接口（需要时复用用户端 /user/doc/category/list 即可，分类树在 0008 迁移内置）。
 * - content_md 是 Markdown 原文；用户端目前直接 pre 展示，没接 markdown-it 渲染。
 * - 仓库失联时后端抛 50001（拦截器弹红 banner）。
 */
export namespace AdminDocApi {
  export interface Article {
    id: number;
    category_id: number;
    slug: string;
    title: string;
    summary?: string;
    content_md?: string;
    status: number;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    page?: number;
    page_size?: number;
  }

  export interface Page {
    list: Article[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    title: string;
    slug: string;
    category_id?: number;
    summary?: string;
    content_md?: string;
    status?: number;
  }

  export interface UpdatePayload {
    id: number;
    title?: string;
    slug?: string;
    category_id?: number;
    summary?: string;
    content_md?: string;
    status?: number;
  }
}

export async function listAdminDocsApi(params?: AdminDocApi.ListParams) {
  return requestClient.get<AdminDocApi.Page>('/admin/doc/article/list', {
    params,
  });
}

export async function createAdminDocApi(data: AdminDocApi.CreatePayload) {
  return requestClient.post('/admin/doc/article/create', data);
}

export async function updateAdminDocApi(data: AdminDocApi.UpdatePayload) {
  return requestClient.put('/admin/doc/article/update', data);
}

export async function deleteAdminDocApi(id: number) {
  return requestClient.delete('/admin/doc/article/delete', { params: { id } });
}
