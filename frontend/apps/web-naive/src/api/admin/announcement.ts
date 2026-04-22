import { requestClient } from '#/api/request';

/**
 * 管理端公告管理。对齐后端 [backend/app/controller/admin/AnnouncementController.php](../../../../../../backend/app/controller/admin/AnnouncementController.php)。
 *   - GET    /admin/announcement/list?keyword=&status=&start_time=&end_time=&page=&page_size=&sort=&order=
 *   - POST   /admin/announcement/create   body: {title*, content, type, status, publish_at?}
 *   - PUT    /admin/announcement/update   body: {id*, ...partial（至少一个字段）}
 *   - DELETE /admin/announcement/delete?id=
 *
 * type 默认 'notice'；publish_at 为定时上线时间（可空，立即生效）。
 */
export namespace AdminAnnouncementApi {
  export interface Announcement {
    id: number;
    title: string;
    content: string;
    type: string;
    status: number;
    publish_at?: null | string;
    created_at?: string;
    updated_at?: string;
  }

  export interface ListParams {
    keyword?: string;
    status?: number | string;
    start_time?: string;
    end_time?: string;
    page?: number;
    page_size?: number;
    sort?: string;
    order?: 'asc' | 'desc';
  }

  export interface Page {
    list: Announcement[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface CreatePayload {
    title: string;
    content?: string;
    type?: string;
    status?: number;
    publish_at?: null | string;
  }

  export type UpdatePayload = Partial<CreatePayload> & { id: number };
}

export async function listAnnouncementsApi(
  params?: AdminAnnouncementApi.ListParams,
) {
  return requestClient.get<AdminAnnouncementApi.Page>(
    '/admin/announcement/list',
    { params },
  );
}

export async function createAnnouncementApi(
  data: AdminAnnouncementApi.CreatePayload,
) {
  return requestClient.post('/admin/announcement/create', data);
}

export async function updateAnnouncementApi(
  data: AdminAnnouncementApi.UpdatePayload,
) {
  return requestClient.put('/admin/announcement/update', data);
}

export async function deleteAnnouncementApi(id: number) {
  return requestClient.delete('/admin/announcement/delete', { params: { id } });
}
