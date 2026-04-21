import type { RouteRecordStringComponent } from '@vben/types';

import { requestClient } from '#/api/request';

/**
 * 后端 /auth/menus 返回的原始菜单行（见 backend/app/repository/mysql/MenuRepository.php）。
 * 数据库 menus 表目前只记最小元数据：id/parent_id/name/path/permission_code/sort，
 * 没有 component/meta 字段。这里在前端按约定补齐，避免把展示细节挤进 DB。
 */
interface BackendMenuRow {
  created_at?: string;
  id: number;
  name: string;
  parent_id: number;
  path: string;
  permission_code: string;
  sort: number;
  status: number;
  updated_at?: string;
}

/**
 * 路径 → 组件路径约定：
 *   /admin/xxx       → component '/admin/xxx/index'（管理端原路径直接映射）
 *   /dashboard       → path '/user/dashboard'，component '/user/dashboard/index'（用户首页特判）
 *   /search etc.     → path '/user/search'，component '/user/search/index'（用户端其它菜单统一加 /user 前缀）
 *
 * 这样菜单结构和路由前缀一一对应，用户/管理端子树互不干扰。
 * 若后端以后在 menus 表上加 component/meta 字段，这里的约定可以逐步退出。
 */
function normalizeUserPath(path: string): string {
  if (path.startsWith('/admin/')) return path;
  if (path === '/dashboard') return '/user/dashboard';
  return `/user${path}`;
}

function componentFromPath(path: string): string {
  return `${path}/index`;
}

/**
 * P0 期间大多数业务视图还没落地，用这个占位组件兜底点击。
 * 必须和 views/_core/placeholder/coming-soon.vue 同步。
 */
const PLACEHOLDER_COMPONENT = '/_core/placeholder/coming-soon';

/**
 * 构建时扫描已落地的 view 文件。对比规则：glob key 像
 *   ../views/user/dashboard/index.vue  →  normalize 成  /user/dashboard/index
 * 若 component 在集合里就直连，否则落到占位组件。
 *
 * 不依赖 @vben/access 内部的 "component 不存在 → not-found" 行为，因为:
 *   1. 占位文案更贴 P0 语境；
 *   2. console.error 会被它刷屏，调试时容易误以为链路坏了。
 */
const viewGlob = import.meta.glob('../../views/**/*.vue');
const existingComponents = new Set<string>(
  Object.keys(viewGlob).map((k) =>
    k.replace(/^.*\/views/, '').replace(/\.vue$/, ''),
  ),
);

function resolveComponent(componentPath: string): string {
  return existingComponents.has(componentPath)
    ? componentPath
    : PLACEHOLDER_COMPONENT;
}

function toRouteRecords(rows: BackendMenuRow[]): RouteRecordStringComponent[] {
  if (!Array.isArray(rows) || rows.length === 0) return [];

  const nodeMap = new Map<
    number,
    RouteRecordStringComponent & { _parentId: number }
  >();

  for (const row of rows) {
    const normalizedPath = normalizeUserPath(row.path);
    const componentPath = resolveComponent(componentFromPath(normalizedPath));
    nodeMap.set(row.id, {
      _parentId: row.parent_id ?? 0,
      component: componentPath,
      name: row.name,
      path: normalizedPath,
      meta: {
        title: row.name,
        authority: [row.permission_code],
      },
      children: [],
    } as any);
  }

  const roots: RouteRecordStringComponent[] = [];
  for (const node of nodeMap.values()) {
    const parent = nodeMap.get(node._parentId);
    if (parent) {
      parent.children = parent.children ?? [];
      parent.children.push(node);
    } else {
      roots.push(node);
    }
  }

  const strip = (list: RouteRecordStringComponent[]) => {
    list.forEach((n) => {
      delete (n as any)._parentId;
      if (n.children && n.children.length > 0) strip(n.children);
      else delete n.children;
    });
  };
  strip(roots);
  return roots;
}

/**
 * 获取当前用户可访问菜单树。
 * 后端：GET /api/v1/auth/menus，按用户权限过滤好。accessMode='backend' 下会被
 * router/access.ts 的 generateAccessible 消费。
 */
export async function getAllMenusApi(): Promise<RouteRecordStringComponent[]> {
  const rows = await requestClient.get<BackendMenuRow[]>('/auth/menus');
  return toRouteRecords(rows ?? []);
}
