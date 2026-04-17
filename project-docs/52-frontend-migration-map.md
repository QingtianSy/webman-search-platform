# 前端迁移映射（legacy → 官方 Vben 基座）

## 当前策略
- `frontend/`：官方 Vben 完整基座（主前端）
- `frontend-legacy/`：旧前端实现（参考源）

## 第一批迁移目标
### 1. 登录与认证
- `frontend-legacy/src/views/auth/LoginView.vue`
  -> `frontend/apps/web-naive/src/views/_core/authentication/login.vue`
- `frontend-legacy/src/stores/auth.ts`
  -> 对照 `frontend/apps/web-naive/src/store` / auth 体系重构
- `frontend-legacy/src/api/auth.ts`
  -> 对照 `frontend/apps/web-naive/src/api` 接入认证请求

### 2. 布局与菜单权限
- `frontend-legacy/src/layouts/AppLayout.vue`
  -> 对照 `frontend/apps/web-naive/src/layouts/*`
- `frontend-legacy/src/utils/menu.ts`
  -> 对照官方路由/菜单组织方式

### 3. 工作台
- `frontend-legacy/src/views/dashboard/DashboardView.vue`
  -> `frontend/apps/web-naive/src/views/dashboard/workspace/*`

## 第二批迁移目标
- 题目列表
- 搜题日志
- API Key
- 钱包/套餐

## 第三批迁移目标
- 文档中心
- 采集任务
- 管理页（用户/角色/权限/菜单/套餐/公告/系统配置等）

## 原则
1. 先迁认证入口和布局，再迁业务页
2. 旧前端仅作为参考，不再继续演进
3. 后端接口保持不变，前端围绕现有 API 做适配
