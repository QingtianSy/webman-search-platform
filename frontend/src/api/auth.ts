import { http } from './http';

export function authLogin(data: { username: string; password: string }) {
  return http.post('/auth/login', data);
}

export function authProfile() {
  return http.get('/auth/profile');
}

export function authMenus() {
  return http.get('/auth/menus');
}

export function authPermissions() {
  return http.get('/auth/permissions');
}
