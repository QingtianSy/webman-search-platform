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

## 关键真接入文档
- [后端真实依赖清单](./project-docs/10-backend-real-dependencies.md)
- [真接入执行批次](./project-docs/11-integration-batches.md)
- [首批真实替换目标](./project-docs/12-first-real-targets.md)
- [第一批真接入执行清单](./project-docs/13-first-integration-execution-checklist.md)
- [宿主机后端接入准备](./project-docs/14-host-backend-prep.md)
- [占位文件替换映射表](./project-docs/15-placeholder-replacement-map.md)
- [Webman 文件准备](./project-docs/16-webman-file-prep.md)
- [Auth/RBAC 真替换方案](./project-docs/17-auth-rbac-replacement-plan.md)
- [Auth/RBAC 表结构草案](./project-docs/18-auth-rbac-schema-draft.md)
- [Auth/RBAC 字段映射](./project-docs/19-auth-rbac-field-mapping.md)
- [Auth/RBAC 执行补充说明](./project-docs/20-auth-rbac-execution-note.md)
