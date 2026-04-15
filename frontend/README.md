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
- `src/views/auth/LoginView.vue`
- `src/views/dashboard/DashboardView.vue`
- `src/views/question/QuestionListView.vue`
- `src/views/logs/SearchLogView.vue`
- `src/views/user/ApiKeyListView.vue`
- `src/views/user/BillingView.vue`
- `src/views/user/DocCenterView.vue`
- `src/views/user/CollectTaskView.vue`
- `src/views/admin/AnnouncementManageView.vue`
- `src/views/admin/SystemConfigView.vue`
- `src/views/admin/DocManageView.vue`
- `src/views/admin/CollectManageView.vue`

## 当前阶段
前端已进入 **页面交互层骨架阶段**：
- 统一登录页已接入 auth store
- 登录后会拉取 profile / menus / permissions
- 已有全局布局壳 AppLayout
- 已有基础样式层
- 已有路由守卫
- 已预留按 menus 动态渲染菜单的结构
- 工作台、题目列表、搜题日志、API Key、钱包套餐页已卡片/表格化
- 管理页已进入表格 + 操作按钮 + 基础表单交互形态

## 后续方向
- 接入真实 Vben Admin 结构
- 接入权限路由守卫细化
- 接入动态菜单从后端 auth/menus 返回
- 接入 Naive UI 页面组件与表格表单
- 将当前页面交互从 mock API 逐步替换为真实接口
