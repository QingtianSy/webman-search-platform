import { http } from './http';

export function getAnnouncementList() {
  return http.get('/admin/announcement/list');
}

export function getSystemConfigList() {
  return http.get('/admin/system-config/list');
}

export function getDocArticleList() {
  return http.get('/admin/doc/article/list');
}

export function getCollectManageList() {
  return http.get('/admin/collect/task/list');
}
