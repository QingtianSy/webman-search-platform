import { http } from './http';

export function getDashboardOverview() {
  return http.get('/user/dashboard/overview');
}

export function getQuestionList(params?: Record<string, any>) {
  return http.get('/admin/question/list', { params });
}

export function getSearchLogs() {
  return http.get('/user/search/logs');
}
