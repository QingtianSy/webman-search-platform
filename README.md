# webman-search-platform

基于 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis + Vben Admin + Naive UI** 的生产级题库搜题 SaaS 平台。

## 当前阶段

当前仓库已推进到：
- 项目总体设计
- 数据库设计定稿
- API 设计定稿
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
- question / search real 分支骨架已落地
- 真接入执行顺序总清单已落地

## 真接入关键文档
- [真接入执行总清单](./project-docs/25-real-integration-master-checklist.md)
- [Auth/RBAC 真替换方案](./project-docs/17-auth-rbac-replacement-plan.md)
- [Auth/RBAC 表结构草案](./project-docs/18-auth-rbac-schema-draft.md)
- [Auth/RBAC 字段映射](./project-docs/19-auth-rbac-field-mapping.md)
- [Auth/RBAC 执行补充说明](./project-docs/20-auth-rbac-execution-note.md)
- [Auth/RBAC Repository 切换方案](./project-docs/22-auth-rbac-repository-swap-plan.md)
- [Question/Search 真实替换方案](./project-docs/24-question-search-replacement-plan.md)
