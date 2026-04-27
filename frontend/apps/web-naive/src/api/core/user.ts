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

/**
 * 获取原始 profile payload（含后端 user.email/user.mobile 等 Vben UserInfo 丢掉的字段）。
 * 个人中心页需要这些字段做回填，单独取一次避免改动全局 UserInfo 形状。
 */
export async function getMyRawProfileApi(): Promise<AuthApi.BackendUser> {
  const raw =
    await requestClient.get<AuthApi.BackendAuthPayload>('/auth/profile');
  return raw.user;
}

/**
 * 更新个人资料。🆕 后端若未实现前端给出友好提示。
 */
export interface UpdateProfilePayload {
  nickname?: string;
  email?: string;
  mobile?: string;
  avatar?: string;
}

export async function updateProfileApi(payload: UpdateProfilePayload) {
  return requestClient.post<void>('/auth/update-profile', payload);
}

/**
 * 修改密码。
 */
export interface ChangePasswordPayload {
  old_password: string;
  new_password: string;
}

export async function changePasswordApi(payload: ChangePasswordPayload) {
  return requestClient.post<void>('/auth/change-password', payload);
}

/**
 * 注销其他会话。🆕 后端未实现则抛错。
 */
export async function invalidateOtherSessionsApi() {
  return requestClient.post<void>('/auth/invalidate-sessions');
}

/**
 * 上传头像。🆕 后端 POST /auth/upload-avatar，multipart/form-data，字段名 `file`。
 * 返回 `{ url: string }` ，前端将 url 赋给 profileForm.avatar 再调 updateProfileApi 落库。
 *
 * 若后端未实现本接口：捕获失败上浮，调用方给出"后端未实现"的 toast 即可。
 */
export interface UploadAvatarResult {
  url: string;
}

export async function uploadAvatarApi(
  file: File,
): Promise<UploadAvatarResult> {
  const form = new FormData();
  form.append('file', file);
  return requestClient.post<UploadAvatarResult>(
    '/auth/upload-avatar',
    form,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  );
}
