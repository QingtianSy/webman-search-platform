import { http } from './http';

export function getApiKeyList() {
  return http.get('/user/api-key/list');
}

export function getWalletDetail() {
  return http.get('/user/wallet/detail');
}

export function getCurrentPlan() {
  return http.get('/user/plan/current');
}

export function getDocConfig() {
  return http.get('/user/doc/config');
}

export function getDocCategories() {
  return http.get('/user/doc/category/list');
}

export function getDocArticleDetail(slug: string) {
  return http.get('/user/doc/article/detail', { params: { slug } });
}

export function getCollectTasks() {
  return http.get('/user/collect/task/list');
}

export function getCollectTaskDetail(task_no: string) {
  return http.get('/user/collect/task/detail', { params: { task_no } });
}
