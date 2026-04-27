import { requestClient } from '#/api/request';

/**
 * 通知公告（用户端只读）。
 * 对齐后端（部分 🆕）：
 *   - GET /user/announcement/list?title=&creator=&type=&unread=&page=&page_size=
 *   - GET /user/announcement/detail?id=   打开详情后端隐式标记已读
 */
export namespace AnnouncementApi {
  export type Type = '公告' | '活动' | '维护' | '通知';

  export interface Item {
    id: number;
    title: string;
    type: number | string;
    status: number;
    creator?: string | null;
    is_pinned?: boolean | number;
    unread?: boolean;
    read_count?: number;
    created_at: string;
    updated_at?: string;
    publish_at?: string | null;
  }

  export interface Detail extends Item {
    content: string; // markdown 或 html，由管理端决定
    content_format?: 'html' | 'markdown';
  }

  export interface ListParams {
    title?: string;
    creator?: string;
    type?: number | string;
    unread?: 0 | 1;
    limit?: number;
    page?: number;
    page_size?: number;
  }

  export interface ListResult {
    list: Item[];
    total: number;
    page: number;
    page_size: number;
  }
}

export async function listAnnouncementsApi(params: AnnouncementApi.ListParams = {}) {
  try {
    return await requestClient.get<AnnouncementApi.ListResult>(
      '/user/announcement/list',
      { params },
    );
  } catch {
    return { list: [], total: 0, page: 1, page_size: params.page_size ?? 10 };
  }
}

export async function getAnnouncementDetailApi(id: number) {
  return requestClient.get<AnnouncementApi.Detail>('/user/announcement/detail', {
    params: { id },
  });
}

/**
 * 显式标记已读（🆕）。后端 detail 接口理论上会隐式打点，但新/旧版本不一定一致，
 * 这里提供一个显式入口，前端 openDetail 后异步调一下；接口不存在或 404 直接吞。
 * 调用失败 **不影响** 详情 Drawer 展示，只影响"已读状态持久化"。
 */
export async function markAnnouncementReadApi(id: number): Promise<void> {
  try {
    await requestClient.post('/user/announcement/read', { id });
  } catch {
    // 后端未实现时静默忽略；依赖 detail 接口的隐式打点兜底
  }
}
