import { defineOverridesPreferences } from '@vben/preferences';

/**
 * @description 项目配置文件
 * 只需要覆盖项目中的一部分配置，不需要的配置不用覆盖，会自动使用默认配置
 * !!! 更改配置后请清空缓存，否则可能不生效
 *
 * 关键覆盖：
 *   - accessMode='backend'：菜单与路由由后端 GET /api/v1/auth/menus 下发；前端不维护 role→route 静态映射
 *   - enableRefreshToken=false：后端无 /auth/refresh，token 失效一律走重登
 *   - defaultHomePath='/user/dashboard'：未带 homePath 时落用户端首页（管理员登录会被 authStore 显式覆盖到 /admin/dashboard）
 */
export const overridesPreferences = defineOverridesPreferences({
  app: {
    name: import.meta.env.VITE_APP_TITLE,
    accessMode: 'backend',
    defaultHomePath: '/user/dashboard',
    enableRefreshToken: false,
  },
  theme: {
    // 品牌主色：teal，与 Vben 默认蓝做区分，保留暗色/亮色可切换
    colorPrimary: 'hsl(174 72% 38%)',
    radius: '0.5',
  },
});
