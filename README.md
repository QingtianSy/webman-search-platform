# webman-search-platform

基于 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis + Vben Admin + Naive UI** 的生产级题库搜题 SaaS 平台。

## 当前项目定位
- 统一用户体系（一个 `users` 表）
- 用户端 / 管理端 / 开放平台
- 题库中心 / 搜题中心 / 用户中心 / 日志中心 / 文档中心 / 采集外围 / 系统配置
- 后端优先按 **Webman / Workerman 官方思路** 收口
- 前端按 **Vben Admin + Naive UI 思路** 收口，但不强行改成官方 monorepo

## 当前状态
当前项目已进入：
# **代码侧生产级方案基本成型，等待后续真实接入执行**

已具备：
- 前后端主模块骨架
- 统一认证与 RBAC 设计
- mock → real 替换路线
- auth/rbac 真接入草案与 SQL/seed
- question/search 真接入草案与脚本链
- user-center / docs / config / collect 外围 real-ready 结构
- 代码侧 smoke tests
- Webman 官方目录结构收口

## 仓库结构
- `backend/` 后端代码
- `frontend/` 前端代码
- `project-docs/` 项目文档
- `scripts/` 脚本与自检
- `infra/` 部署模板

## 当前建议
如果后续开始真实部署 / 宝塔搭建，建议按：
1. Phase 1：依赖与运行时准备
2. Phase 2：auth/rbac 真接入
3. Phase 3：question/search 真接入
4. Phase 4：用户中心 / 文档 / 配置 / 采集逐步切 real

## 关键文档
- `project-docs/25-real-integration-master-checklist.md`
- `project-docs/27-project-final-convergence-checklist.md`
- `project-docs/30-auth-rbac-runbook.md`
- `project-docs/31-question-search-runbook.md`
- `project-docs/38-minis-runtime-verification.md`
- `project-docs/43-official-doc-final-decision.md`
