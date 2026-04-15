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
- `src/api/http.ts`
- `src/api/auth.ts`
- `src/api/access.ts`
- `src/api/business.ts`
- `src/api/user.ts`
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

## 当前页面目标
- 登录页：统一认证入口
- 工作台页：用户中心首页卡片页
- 题目列表页：管理端表格页
- 搜题日志页：用户侧表格页
- API Key 页：用户列表页
- 钱包套餐页：用户卡片页
- 文档中心页：文档配置页
- 采集任务页：采集中心页
- 公告管理页：管理端骨架页
- 系统配置页：管理端骨架页
- 文档管理页：管理端骨架页
- 采集管理页：管理端骨架页

## 当前阶段
前端已进入 **后台卡片/表格形态阶段**：
- 统一登录页已接入 auth store
- 登录后会拉取 profile / menus / permissions
- 已有全局布局壳 AppLayout
- 已有路由守卫
- 已预留按 menus 动态渲染菜单的结构
- 工作台、题目列表、搜题日志、API Key、钱包套餐页已从 pre 文本升级为卡片/表格形态

## 后续方向
- 接入真实 Vben Admin 结构
- 接入权限路由守卫细化
- 接入动态菜单从后端 auth/menus 返回
- 接入 Naive UI 页面组件与表格表单
