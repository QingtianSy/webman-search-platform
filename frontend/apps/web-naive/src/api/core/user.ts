import type { UserInfo } from '@vben/types';

import { type AuthApi, toUserInfo } from '#/api/core/auth';
import { requestClient } from '#/api/request';

/**
 * 获取当前登录用户信息。
 * 后端 GET /auth/profile 返回和 /auth/login 一致的 payload（不含 token），
 * 这里复用 auth 模块的 toUserInfo 映射，保证刷新页面/重新挂载时用户信息结构一致。
 */
export async function getUserInfoApi(): Promise<UserInfo> {
  const raw =
    await requestClient.get<AuthApi.BackendAuthPayload>('/auth/profile');
  return toUserInfo(raw);
}
