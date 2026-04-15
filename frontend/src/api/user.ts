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

export function getCollectTasks() {
  return http.get('/user/collect/task/list');
}
