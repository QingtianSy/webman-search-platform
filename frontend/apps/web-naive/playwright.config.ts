import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright 冒烟配置
 *
 * 仅覆盖最小启动链路（登录页渲染 + 标题），不依赖后端可用。
 * 运行前先执行 `pnpm test:e2e:install` 安装 Chromium。
 *
 * 本地运行：
 *   1. 在一个终端起前端：pnpm dev（默认 5555 端口，详见 vite 配置）
 *   2. 另一个终端：pnpm test:e2e
 */
const PORT = Number(process.env.E2E_PORT || 5555);
const BASE_URL = process.env.E2E_BASE_URL || `http://localhost:${PORT}`;

export default defineConfig({
  testDir: './e2e',
  timeout: 30_000,
  expect: { timeout: 5_000 },
  fullyParallel: false,
  retries: 0,
  reporter: [['list']],
  use: {
    baseURL: BASE_URL,
    trace: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
