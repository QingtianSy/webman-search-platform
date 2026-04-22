import { requestClient } from '#/api/request';

/** 管理端公告管理。 */
export namespace AdminAnnouncementApi {
  export interface AnnouncementItem {
    id: number;
    title: string;
    content: string;
    status: number;
    publish_at?: string;
    created_at?: string;
  }
}

type PageResult<T> = { items: T[]; total: number };

export async function listAnnouncementsApi(params?: { page?: number; page_size?: number }) {
  return requestClient.get<PageResult<AdminAnnouncementApi.AnnouncementItem>>('/admin/announcements', { params });
}

export async function createAnnouncementApi(data: Partial<AdminAnnouncementApi.AnnouncementItem>) {
  return requestClient.post<AdminAnnouncementApi.AnnouncementItem>('/admin/announcements', data);
}

export async function updateAnnouncementApi(id: number, data: Partial<AdminAnnouncementApi.AnnouncementItem>) {
  return requestClient.put<AdminAnnouncementApi.AnnouncementItem>(`/admin/announcements/${id}`, data);
}

export async function deleteAnnouncementApi(id: number) {
  return requestClient.delete(`/admin/announcements/${id}`);
}
