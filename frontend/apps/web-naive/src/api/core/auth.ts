import type { UserInfo } from '@vben/types';

import { requestClient } from '#/api/request';

/**
 * 与后端 AuthService::buildAuthPayload 对齐（见 backend/app/service/auth/AuthService.php）。
 * 后端返回的 raw payload 形如：
 *   { user: { id, username, nickname, avatar, status, ... },
 *     roles: ['user' | 'admin' | ...],
 *     permissions: ['admin.access', ...],
 *     menus: [...],
 *     default_portal: 'admin' | 'portal',
 *     token?: string   // 仅 /auth/login、/auth/register 带；/auth/profile 不带
 *   }
 * Vben 内部期望 UserInfo 满足 BasicUserInfo: { userId, username, realName, avatar, roles, homePath }。
 * 这里的映射层是整个前端与后端耦合的"唯一字段翻译点"，业务组件只认 Vben 的 UserInfo。
 */
export namespace AuthApi {
  export interface LoginParams {
    password?: string;
    username?: string;
  }

  export interface RegisterParams {
    nickname?: string;
    password: string;
    username: string;
  }

  export interface BackendUser {
    avatar?: null | string;
    id: number;
    nickname?: null | string;
    status?: number;
    username: string;
  }

  export interface BackendAuthPayload {
    default_portal: string;
    menus?: unknown[];
    permissions: string[];
    roles: string[];
    token?: string;
    user: BackendUser;
  }

  /** 适配后的登录返回：accessToken 给 accessStore；userInfo 给 userStore；permissions 给 accessStore.setAccessCodes */
  export interface LoginResult {
    accessToken: string;
    permissions: string[];
    userInfo: UserInfo;
  }
}

/**
 * default_portal === 'admin' → /admin/dashboard，否则落用户首页。
 * 后端对普通用户返回的是 'portal'，与前端前缀 /user 不同名，这里收敛一次。
 */
function resolveHomePath(defaultPortal: string): string {
  return defaultPortal === 'admin' ? '/admin/dashboard' : '/user/dashboard';
}

function toUserInfo(payload: AuthApi.BackendAuthPayload): UserInfo {
  const { user, roles, default_portal } = payload;
  return {
    userId: String(user.id),
    username: user.username,
    realName: user.nickname || user.username,
    avatar: user.avatar ?? '',
    roles,
    homePath: resolveHomePath(default_portal),
    desc: '',
    token: payload.token ?? '',
  } as UserInfo;
}

export async function loginApi(
  data: AuthApi.LoginParams,
): Promise<AuthApi.LoginResult> {
  const raw = await requestClient.post<AuthApi.BackendAuthPayload>(
    '/auth/login',
    data,
  );
  return {
    accessToken: raw.token ?? '',
    userInfo: toUserInfo(raw),
    permissions: raw.permissions ?? [],
  };
}

/**
 * 注册：后端 /auth/register 返回与 /auth/login 同构的 payload（含 token），
 * 所以这里直接复用 LoginResult 形状，让 authStore 能用同一套装配逻辑。
 * 后端限流 5/60s/IP；字段校验见 RegisterValidate（用户名 3-50，密码 >=6）。
 */
export async function registerApi(
  data: AuthApi.RegisterParams,
): Promise<AuthApi.LoginResult> {
  const raw = await requestClient.post<AuthApi.BackendAuthPayload>(
    '/auth/register',
    data,
  );
  return {
    accessToken: raw.token ?? '',
    userInfo: toUserInfo(raw),
    permissions: raw.permissions ?? [],
  };
}

/**
 * 登出：后端 POST /auth/logout 会 bump sessions_invalidated_at，让当前 token 失效。
 */
export async function logoutApi() {
  return requestClient.post('/auth/logout');
}

/**
 * Vben 原模板通过 /auth/codes 获取权限码。
 * 我方后端把 permissions 打包进 /auth/login 与 /auth/profile，这里复用 profile 避免多一次请求。
 * 保留同名导出，便于 Vben 原组件代码直接使用。
 */
export async function getAccessCodesApi(): Promise<string[]> {
  const raw = await requestClient.get<AuthApi.BackendAuthPayload>(
    '/auth/profile',
  );
  return raw.permissions ?? [];
}

export { toUserInfo };
