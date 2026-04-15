import { defineStore } from 'pinia';

type UserInfo = Record<string, any> | null;

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: null as UserInfo,
    roles: [] as string[],
    permissions: [] as string[],
    menus: [] as any[],
    defaultPortal: 'portal',
  }),
  actions: {
    setAuthPayload(payload: any) {
      this.token = payload.token || '';
      this.user = payload.user || null;
      this.roles = payload.roles || [];
      this.permissions = payload.permissions || [];
      this.menus = payload.menus || [];
      this.defaultPortal = payload.default_portal || 'portal';
      if (this.token) {
        localStorage.setItem('token', this.token);
      }
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
