/**
 * 请求层适配：与后端响应规范对齐。
 *
 * 后端约定（详见 docs/04-API接口文档.md）：
 *   - 成功：{ code: 1, msg: 'success', data: ... }
 *   - 业务错误：code 在 40001 / 40002 / 40003 / 40006 / 50001 等
 *   - HTTP 状态恒为 200，错误一律走 body code
 *
 * 与 Vben 默认模板的差异：
 *   - successCode 由 0 改为 1
 *   - 错误文案字段由 error/message 改为 msg
 *   - Vben 内置 authenticateResponseInterceptor 仅识别 HTTP 401，需在它之后再插一层
 *     自定义拦截器，按 code 派发：40002 → 触发重认证；40003/40006/50001 → toast；其它 → 透传
 *   - 后端无 /auth/refresh，preferences.app.enableRefreshToken 已置 false，doRefreshToken 不会被调用
 */
import type { RequestClientOptions } from '@vben/request';

import { useAppConfig } from '@vben/hooks';
import { preferences } from '@vben/preferences';
import {
  authenticateResponseInterceptor,
  defaultResponseInterceptor,
  errorMessageResponseInterceptor,
  RequestClient,
} from '@vben/request';
import { useAccessStore } from '@vben/stores';

import { message } from '#/adapter/naive';
import { useAuthStore } from '#/store';

const { apiURL } = useAppConfig(import.meta.env, import.meta.env.PROD);

function createRequestClient(baseURL: string, options?: RequestClientOptions) {
  const client = new RequestClient({
    ...options,
    baseURL,
  });

  /**
   * 重认证：清 token + 跳登录（或在 modal 模式下展示登录弹窗）
   *
   * 轮询场景下（支付页 3s / 支付记录 60s / 采集 5s）token 失效瞬间可能有多个请求
   * 同时命中 40002，不加锁会反复 setAccessToken(null) + logout()，触发多次路由跳转 / modal 弹出。
   * 用模块级 in-flight flag 收敛：一次窗口内只真正 reAuth 一次，其它 40002 安静吞掉。
   */
  let reAuthInFlight = false;
  async function doReAuthenticate() {
    if (reAuthInFlight) return;
    reAuthInFlight = true;
    try {
      const accessStore = useAccessStore();
      const authStore = useAuthStore();
      accessStore.setAccessToken(null);
      if (
        preferences.app.loginExpiredMode === 'modal' &&
        accessStore.isAccessChecked
      ) {
        accessStore.setLoginExpired(true);
      } else {
        await authStore.logout();
      }
    } finally {
      // 跳转/logout 完成后才解锁；如果进了 modal 模式，modal 关闭意味着用户重新登录，这里锁释不释放都不影响（拿到新 token 不会再 40002）
      reAuthInFlight = false;
    }
  }

  /**
   * refreshToken 占位：后端无此能力，preferences 已禁用，函数仅为类型完整保留。
   */
  async function doRefreshToken() {
    return '';
  }

  function formatToken(token: null | string) {
    return token ? `Bearer ${token}` : null;
  }

  // 请求头：Authorization + Accept-Language
  client.addRequestInterceptor({
    fulfilled: async (config) => {
      const accessStore = useAccessStore();
      config.headers.Authorization = formatToken(accessStore.accessToken);
      config.headers['Accept-Language'] = preferences.app.locale;
      return config;
    },
  });

  // 响应数据格式拦截：code===1 → 解出 data；否则抛错（包内含 response）
  client.addResponseInterceptor(
    defaultResponseInterceptor({
      codeField: 'code',
      dataField: 'data',
      successCode: 1,
    }),
  );

  // HTTP 401 通道（极少触发；后端正常不返 401）
  client.addResponseInterceptor(
    authenticateResponseInterceptor({
      client,
      doReAuthenticate,
      doRefreshToken,
      enableRefreshToken: preferences.app.enableRefreshToken,
      formatToken,
    }),
  );

  // 业务错误码派发：40002 → 重认证；40003 → 无权；40006 → 配额；50001 → 服务异常 banner
  // 注意：后注册者后包装，最先执行；这里需要在 errorMessage 之前生效，所以放在它之前注册。
  //
  // Toast 节流：轮询场景（支付页 3s / 支付记录 60s / 采集 5s）如果后端在抖动，50001 会连环触发，
  // 刷屏影响用户操作。同一 (code + msg) 5 秒内只弹一次，保留第一条提示即可。
  const toastCooldown = new Map<string, number>();
  function maybeToast(
    level: 'error' | 'warning',
    code: number,
    msg: string,
  ) {
    const key = `${code}:${msg}`;
    const now = Date.now();
    const last = toastCooldown.get(key) ?? 0;
    if (now - last < 5000) return;
    toastCooldown.set(key, now);
    // 懒清理：尺寸涨到 50 时清一次（5s 窗口内很难累积）
    if (toastCooldown.size > 50) toastCooldown.clear();
    level === 'error' ? message.error(msg) : message.warning(msg);
  }

  client.addResponseInterceptor({
    rejected: async (error: any) => {
      const responseData = error?.response?.data;
      const code = responseData?.code;
      const msg = responseData?.msg ?? responseData?.message ?? '';

      if (typeof code === 'number') {
        switch (code) {
          case 40002: {
            // 未登录 / token 失效 — 让 doReAuthenticate 处理跳转，吞掉错误避免页面再弹一次 toast
            await doReAuthenticate();
            return Promise.reject(error);
          }
          case 40003: {
            maybeToast('error', 40003, msg || '权限不足');
            return Promise.reject(error);
          }
          case 40006: {
            maybeToast('warning', 40006, msg || '账户配额不足，请充值或升级套餐');
            return Promise.reject(error);
          }
          case 50001: {
            maybeToast('error', 50001, msg || '服务暂不可用，请稍后重试');
            return Promise.reject(error);
          }
          default: {
            // 其它业务错误码（如 40001 / 40004）走通用文案
            if (msg) {
              maybeToast('error', code, msg);
              return Promise.reject(error);
            }
          }
        }
      }
      // 没有 code 字段 / 非 200 HTTP / 网络错误 → 落到下一层 errorMessage 处理
      return Promise.reject(error);
    },
  });

  // 兜底错误提示：网络错误 / HTTP 4xx 5xx / 未带 code 的异常
  client.addResponseInterceptor(
    errorMessageResponseInterceptor((msg: string, error) => {
      const responseData = error?.response?.data ?? {};
      const errorMessage = responseData?.msg ?? responseData?.message ?? '';
      message.error(errorMessage || msg);
    }),
  );

  return client;
}

export const requestClient = createRequestClient(apiURL, {
  responseReturn: 'data',
});

export const baseRequestClient = new RequestClient({ baseURL: apiURL });
