import { defineConfig } from '@vben/vite-config';

/**
 * 开发代理：
 *   - 默认 target 指向本地 Webman（见 backend/config/server.php: http://0.0.0.0:8787）
 *   - 去除 rewrite：后端路由就是 /api/v1/...，前端请求也以 /api 开头，不需要剥前缀
 *   - 如需切回 Nitro mock，设置 VITE_DEV_PROXY_TARGET=http://localhost:5320/api 且 VITE_NITRO_MOCK=true，
 *     同时在下面 rewrite 上打开注释
 */
export default defineConfig(async () => {
  const proxyTarget =
    process.env.VITE_DEV_PROXY_TARGET ?? 'http://127.0.0.1:8787';

  return {
    application: {},
    vite: {
      server: {
        proxy: {
          '/api': {
            changeOrigin: true,
            target: proxyTarget,
            ws: true,
          },
        },
      },
    },
  };
});
