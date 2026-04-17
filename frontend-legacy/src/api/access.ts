import { http } from './http';

export function getAuthMenus() {
  return http.get('/auth/menus');
}

export function getAuthPermissions() {
  return http.get('/auth/permissions');
}
