import type { AppMenuItem } from '../types/menu';

export function normalizeMenus(menus: any[] = []): AppMenuItem[] {
  return menus
    .filter(Boolean)
    .map((item) => ({
      id: item.id,
      name: item.name,
      path: String(item.path || '').startsWith('/') ? String(item.path) : '/' + String(item.path || ''),
      permission_code: item.permission_code,
    }))
    .filter((item) => item.name && item.path);
}

export function filterMenusByPermissions(menus: AppMenuItem[], permissions: string[]): AppMenuItem[] {
  if (!permissions.length) return menus;
  return menus.filter((item) => !item.permission_code || permissions.includes(item.permission_code));
}
