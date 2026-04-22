import { requestClient } from '#/api/request';

/**
 * 用户端文档中心（只读）。管理端写入接口在 api/admin/doc.ts。
 */
export namespace DocApi {
  export interface DocCategory {
    id: number;
    name: string;
    parent_id: number;
    sort: number;
    children?: DocCategory[];
  }

  export interface DocItem {
    id: number;
    title: string;
    category_id: number;
    summary?: string;
    updated_at?: string;
  }

  export interface DocDetail extends DocItem {
    content: string;
  }
}

export async function listDocCategoriesApi() {
  return requestClient.get<DocApi.DocCategory[]>('/docs/categories');
}

export async function listDocsApi(params?: { category_id?: number; keyword?: string; page?: number; page_size?: number }) {
  return requestClient.get<{ items: DocApi.DocItem[]; total: number }>('/docs', { params });
}

export async function getDocDetailApi(id: number) {
  return requestClient.get<DocApi.DocDetail>(`/docs/${id}`);
}
