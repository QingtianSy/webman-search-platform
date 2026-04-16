# webman-search-platform

一个按 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis** 方向构建的题库搜题平台项目。

## 当前阶段
项目已进入：
- **代码侧生产级方案基本成型**
- **auth/rbac 真实主线已打通**
- **question/search 真实主线已打通**
- **backend 入口已进一步按官方 Webman 方式收口**
- **代码侧 smoke tests 与质量门禁已具备**

## 当前结构
- `backend/`：Webman 后端代码
- `frontend/`：统一前端项目（按 Vben Admin + Naive UI 思路组织）
- `project-docs/`：数据库、API、mock->real、runbook、收口文档
- `scripts/`：部署、检查、schema/seed、smoke tests、巡检脚本
- `infra/`：Nginx / systemd 模板

## 当前重点
当前更推荐优先做：
1. 后端按官方 Webman 继续收口
2. 逐步真实接入 auth/rbac、question/search、user-center
3. 后续再统一进入宝塔部署与联调

## 关键文档
- `project-docs/25-real-integration-master-checklist.md`
- `project-docs/27-project-final-convergence-checklist.md`
- `project-docs/39-official-doc-alignment-review.md`
- `project-docs/40-workerman-memory-safety-checklist.md`
- `project-docs/43-official-doc-final-decision.md`
- `project-docs/48-webman-runtime-takeover-boundary.md`
- `project-docs/49-search-real-http-prep.md`

## 当前结论
当前仓库已经达到：
**可以继续在代码侧完善，也可以在条件成熟时进入宝塔真实接入执行。**
