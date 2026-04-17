import { http } from './http';

export function getAnnouncementList() {
  return http.get('/admin/announcement/list');
}

export function createAnnouncement(data: Record<string, any>) {
  return http.post('/admin/announcement/create', data);
}

export function updateAnnouncement(data: Record<string, any>) {
  return http.put('/admin/announcement/update', data);
}

export function deleteAnnouncement(id: number) {
  return http.delete('/admin/announcement/delete', { params: { id } });
}

export function getSystemConfigList() {
  return http.get('/admin/system-config/list');
}

export function updateSystemConfig(data: Record<string, any>) {
  return http.post('/admin/system-config/update', data);
}

export function getDocArticleList() {
  return http.get('/admin/doc/article/list');
}

export function createDocArticle(data: Record<string, any>) {
  return http.post('/admin/doc/article/create', data);
}

export function updateDocArticle(data: Record<string, any>) {
  return http.put('/admin/doc/article/update', data);
}

export function deleteDocArticle(id: number) {
  return http.delete('/admin/doc/article/delete', { params: { id } });
}

export function getCollectManageList() {
  return http.get('/admin/collect/task/list');
}

export function stopCollectTask(task_no: string) {
  return http.post('/admin/collect/task/stop', { task_no });
}

export function retryCollectTask(task_no: string) {
  return http.post('/admin/collect/task/retry', { task_no });
}

export function getQuestionList(params?: Record<string, any>) {
  return http.get('/admin/question/list', { params });
}

export function getQuestionDetail(id: number) {
  return http.get('/admin/question/detail', { params: { id } });
}

export function createQuestion(data: Record<string, any>) {
  return http.post('/admin/question/create', data);
}

export function updateQuestion(data: Record<string, any>) {
  return http.put('/admin/question/update', data);
}

export function deleteQuestion(id: number) {
  return http.delete('/admin/question/delete', { params: { id } });
}

export function getAdminUsers() {
  return http.get('/admin/user/list');
}

export function getAdminRoles() {
  return http.get('/admin/role/list');
}

export function getAdminPermissions() {
  return http.get('/admin/permission/list');
}

export function getAdminMenus() {
  return http.get('/admin/menu/list');
}

export function getAdminPlans() {
  return http.get('/admin/plan/list');
}
