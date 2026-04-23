import { expect, test } from '@playwright/test';

/**
 * 冒烟用例：仅验证前端静态壳正常启动
 *
 * 不走真实登录（需要后端），因此也不做用户数据断言。
 * 目的是在 CI 里保证构建 + 首屏路由不炸。
 */
test.describe('web-naive smoke', () => {
  test('登录页可访问且渲染账号输入框', async ({ page }) => {
    await page.goto('/auth/login');
    // 页面标题应带应用名
    await expect(page).toHaveTitle(/./);
    // 登录表单：用户名输入框（Naive UI n-input 会渲染原生 input）
    const usernameInput = page.locator('input').first();
    await expect(usernameInput).toBeVisible({ timeout: 10_000 });
  });

  test('访问根路径应跳转到登录页（未登录）', async ({ page }) => {
    await page.goto('/');
    await page.waitForURL(/\/auth\/login/, { timeout: 10_000 });
    expect(page.url()).toMatch(/\/auth\/login/);
  });
});
