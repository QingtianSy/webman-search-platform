import type { Recordable, UserInfo } from '@vben/types';

import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { LOGIN_PATH } from '@vben/constants';
import { preferences } from '@vben/preferences';
import { resetAllStores, useAccessStore, useUserStore } from '@vben/stores';

import { defineStore } from 'pinia';

import { notification } from '#/adapter/naive';
import { getUserInfoApi, loginApi, logoutApi } from '#/api';
import { $t } from '#/locales';

/**
 * 登录态 store。与 Vben 原模板的主要差异：
 *   - loginApi 已在适配层把 user + permissions 打包返回，不再在这里并发二次拉（原版 Promise.all(fetchUserInfo, getAccessCodesApi)）
 *   - homePath 由 loginApi 内按 default_portal 计算好，这里只负责把 userInfo 灌进 store 并按 homePath 跳转
 *   - 登出：后端 /auth/logout 已 bump sessions_invalidated_at，本地只需清 store + 回登录页
 */
export const useAuthStore = defineStore('auth', () => {
  const accessStore = useAccessStore();
  const userStore = useUserStore();
  const router = useRouter();

  const loginLoading = ref(false);

  async function authLogin(
    params: Recordable<any>,
    onSuccess?: () => Promise<void> | void,
  ) {
    let userInfo: null | UserInfo = null;
    try {
      loginLoading.value = true;
      const { accessToken, userInfo: loggedInUser, permissions } =
        await loginApi(params);

      if (accessToken) {
        accessStore.setAccessToken(accessToken);
        userStore.setUserInfo(loggedInUser);
        accessStore.setAccessCodes(permissions);
        userInfo = loggedInUser;

        if (accessStore.loginExpired) {
          accessStore.setLoginExpired(false);
        } else {
          onSuccess
            ? await onSuccess?.()
            : await router.push(
                loggedInUser.homePath || preferences.app.defaultHomePath,
              );
        }

        if (loggedInUser?.realName) {
          notification.success({
            content: $t('authentication.loginSuccess'),
            description: `${$t('authentication.loginSuccessDesc')}:${loggedInUser.realName}`,
            duration: 3000,
          });
        }
      }
    } finally {
      loginLoading.value = false;
    }

    return { userInfo };
  }

  async function logout(redirect: boolean = true) {
    try {
      await logoutApi();
    } catch {
      // 后端 50001 等情况下 logoutApi 会抛；本地仍然必须清干净，否则用户困在半登录态。
    }
    resetAllStores();
    accessStore.setLoginExpired(false);

    await router.replace({
      path: LOGIN_PATH,
      query: redirect
        ? {
            redirect: encodeURIComponent(router.currentRoute.value.fullPath),
          }
        : {},
    });
  }

  /**
   * 刷新页面后用来恢复用户信息。未登录时 /auth/profile 会被 request 拦截器按 40002 处理并跳登录。
   */
  async function fetchUserInfo() {
    const userInfo = await getUserInfoApi();
    userStore.setUserInfo(userInfo);
    return userInfo;
  }

  function $reset() {
    loginLoading.value = false;
  }

  return {
    $reset,
    authLogin,
    fetchUserInfo,
    loginLoading,
    logout,
  };
});
