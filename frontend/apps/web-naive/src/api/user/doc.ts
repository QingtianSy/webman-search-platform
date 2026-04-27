import { requestClient } from '#/api/request';

/**
 * 用户端文档中心。对齐后端 [backend/app/controller/user/DocController.php](../../../../../../backend/app/controller/user/DocController.php)。
 *   - GET /user/doc/category/list?page=&page_size=
 *   - GET /user/doc/article/detail?slug=
 *   - GET /user/doc/config
 *
 * 注意：后端目前没有"按分类列文章"的用户端接口（只有管理端 /admin/doc/article/list）。
 * 用户端文章入口需要通过 slug 直连，典型路径：公告/首页链接 → /user/doc?slug=xxx。
 */
export namespace UserDocApi {
  export interface Category {
    id: number;
    name: string;
    slug: string;
    sort: number;
    status: number;
    created_at?: string;
    updated_at?: string;
  }

  export interface CategoryPage {
    list: Category[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface Article {
    id: number;
    category_id: number;
    slug: string;
    title: string;
    summary?: string;
    content_md: string;
    status: number;
    created_at?: string;
    updated_at?: string;
  }

  /** /user/doc/config 已在服务层剥除 api_key；只保留可公开字段。 */
  export interface PublicConfig {
    multimodal_model?: string;
    text_model?: string;
    providers?: Array<{ name: string; value: string; desc?: string }>;
    [k: string]: any;
  }
}

export async function listDocCategoriesApi(params?: {
  page?: number;
  page_size?: number;
}) {
  return requestClient.get<UserDocApi.CategoryPage>('/user/doc/category/list', {
    params,
  });
}

export async function getDocArticleApi(slug: string) {
  return requestClient.get<UserDocApi.Article>('/user/doc/article/detail', {
    params: { slug },
  });
}

export async function getDocConfigApi() {
  try {
    return await requestClient.get<UserDocApi.PublicConfig>('/user/doc/config');
  } catch {
    return {} as UserDocApi.PublicConfig;
  }
}

/**
 * 对接文档元信息（API 基础地址、默认 key、头部名）。
 * 🆕 后端未实现时返回浏览器端默认兜底。
 */
export interface DocMeta {
  api_base_url: string;
  default_api_key?: string;
  header_name: string;
}

export async function getDocMetaApi(): Promise<DocMeta> {
  try {
    const r = await requestClient.get<DocMeta>('/user/doc/meta');
    if (r && (r.api_base_url || r.header_name)) return r;
    throw new Error('empty');
  } catch {
    return {
      api_base_url: `${window.location.origin}/api/v1/open/v1`,
      header_name: 'x-api-secret',
    };
  }
}
