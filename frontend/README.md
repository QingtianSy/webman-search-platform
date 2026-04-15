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
- `src/api/http.ts`
- `src/api/auth.ts`
- `src/api/business.ts`
- `src/views/auth/LoginView.vue`
- `src/views/dashboard/DashboardView.vue`
- `src/views/question/QuestionListView.vue`
- `src/views/logs/SearchLogView.vue`

## 当前页面目标
- 登录页：统一认证入口
- 工作台页：用户中心首页
- 题目列表页：管理员示例页
- 搜题日志页：用户日志示例页

## 后续方向
- 接入真实 Vben Admin 结构
- 接入权限路由守卫
- 接入动态菜单
- 接入 Naive UI 页面组件
