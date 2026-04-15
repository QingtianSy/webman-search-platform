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
- 前端统一登录、布局、认证状态、动态菜单预留
- 前端工作台/题目列表/搜题日志/API Key/钱包套餐页面卡片表格化
- 前端管理页已进入带基础操作按钮与表单交互的雏形阶段
- 批次 1 真接入文件准备已开始
- 批次 1 真接入代码级准备已开始
- auth / rbac 真替换草案、字段映射、schema SQL、seed SQL 已落地
- auth / rbac real 查询骨架已落地
- question / search real 分支骨架已落地
- Phase 1 真实依赖接入执行文档已落地

## 关键文档
- [Phase 1 真实依赖接入执行文档](./project-docs/26-phase1-runtime-execution.md)
- [真接入执行总清单](./project-docs/25-real-integration-master-checklist.md)
- [Auth/RBAC 真替换方案](./project-docs/17-auth-rbac-replacement-plan.md)
- [Question/Search 真实替换方案](./project-docs/24-question-search-replacement-plan.md)
