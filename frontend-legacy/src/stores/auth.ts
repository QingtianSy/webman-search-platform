import { defineStore } from 'pinia';
import type { AppMenuItem } from '../types/menu';

type UserInfo = Record<string, any> | null;

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: null as UserInfo,
    roles: [] as string[],
    permissions: [] as string[],
    menus: [] as AppMenuItem[],
    defaultPortal: 'portal',
  }),
  actions: {
    setAuthPayload(payload: any) {
      this.token = payload.token || this.token || '';
      this.user = payload.user || this.user;
      this.roles = payload.roles || this.roles;
      this.permissions = payload.permissions || this.permissions;
      this.menus = payload.menus || this.menus;
      this.defaultPortal = payload.default_portal || this.defaultPortal || 'portal';
      if (this.token) {
        localStorage.setItem('token', this.token);
      }
    },
    setMenus(menus: AppMenuItem[]) {
      this.menus = menus || [];
    },
    setPermissions(permissions: string[]) {
      this.permissions = permissions || [];
    },
    logout() {
      this.token = '';
      this.user = null;
      this.roles = [];
      this.permissions = [];
      this.menus = [];
      this.defaultPortal = 'portal';
      localStorage.removeItem('token');
    },
  },
});
