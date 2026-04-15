# frontend

统一前端项目骨架，采用：
- 一个登录页
- 一个前端项目
- 统一用户体系
- 按角色和权限动态决定进入用户端或管理端模块

## 当前已落地
- `package.json`
- `index.html`
- `src/main.ts`
- `src/App.vue`
- `src/router/index.ts`
- `src/stores/auth.ts`
- `src/layouts/AppLayout.vue`
- `src/styles/base.css`
- `src/api/http.ts`
- `src/api/auth.ts`
- `src/api/access.ts`
- `src/api/business.ts`
- `src/api/user.ts`
- `src/api/admin.ts`
- 用户页与管理页基础骨架

## 当前阶段
前端已进入 **更完整的后台壳阶段**：
- 统一登录页已接入 auth store
- 登录后会拉取 profile / menus / permissions
- 已有全局布局壳 AppLayout
- 已有基础样式层
- 已有路由守卫
- 已预留按 menus 动态渲染菜单的结构
- 已补齐用户侧核心页面
- 已补齐管理侧核心页面骨架（用户/角色/权限/菜单/套餐/公告/系统配置/文档/采集）

## 后续方向
- 接入真实 Vben Admin 结构
- 接入权限路由守卫细化
- 接入动态菜单从后端 auth/menus 返回
- 接入 Naive UI 页面组件与表格表单
- 将当前页面交互从 mock API 逐步替换为真实接口
