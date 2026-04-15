# webman-search-platform

基于 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis + Vben Admin + Naive UI** 的生产级题库搜题 SaaS 平台。

## 当前阶段

当前仓库已推进到：
- 项目总体设计
- 数据库设计已收敛为统一用户体系（一个 users 表 + 角色权限）
- API 设计已收敛为统一认证主入口（/api/v1/auth/*）
- 项目目录规划
- 宿主机生产部署方案
- 后端真实业务骨架
- 统一用户体系与 RBAC 模型
- 管理端操作型接口第三轮
- 多存储 mock 数据源
- Python 巡检与状态汇总脚本
- Mock → Real 替换路线
- Webman 真接入准备说明
- auth / rbac 真替换草案、字段映射、schema SQL、seed SQL 已落地
- auth / rbac real 查询骨架已落地
- auth / rbac 可执行脚本链已落地
- question / search real 分支骨架已落地
- question / search 可执行脚本链已落地
- user-center / docs / config / collect 外围已 real-ready
- 前端统一登录、布局、认证状态、动态菜单预留
- 前端页面已具备卡片 / 表格 / 基础交互层
- 代码侧 smoke tests 已覆盖关键主线
- 已基于官方文档完成最终判断：**不推倒重来，保持当前业务结构，继续吸收官方最佳实践**

## 关键文档
- [官方文档最终判断](./project-docs/43-official-doc-final-decision.md)
- [官方文档对齐审查](./project-docs/39-official-doc-alignment-review.md)
- [Workerman 长驻进程安全清单](./project-docs/40-workerman-memory-safety-checklist.md)
- [Vben 对齐与前端收口计划](./project-docs/41-vben-alignment-plan.md)
- [代码侧自检说明](./project-docs/37-code-smoke-tests.md)
