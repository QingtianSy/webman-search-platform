# webman-search-platform

基于 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis + Vben Admin + Naive UI** 的生产级题库搜题 SaaS 平台。

## 项目定位

这是一个可商用的题库搜题平台，包含：

- 用户端门户
- 管理端后台
- 搜题中心
- 题库中心
- 采集中心
- 开放平台 API
- 套餐计费中心
- 日志审计中心

## 技术栈

### 后端
- PHP 8.2
- Webman 2.x
- Workerman 5.x
- Swoole 5.1.x

### 数据层
- MySQL 8.0
- MongoDB 7
- Elasticsearch 8
- Redis 7.2

### 前端
- Node.js 20 LTS
- Vue 3
- Vite 5
- TypeScript 5
- Vben Admin
- Naive UI

### 部署
- Ubuntu 22.04 LTS
- Nginx
- systemd
- 宿主机直装，不使用 Docker Compose
- Shell + Python 混合运维脚本

## 仓库结构

```bash
backend/                 # Webman 后端
frontend/                # 统一前端项目骨架
infra/                   # 宿主机部署脚本与配置模板
project-docs/            # 产品、架构、数据库、接口设计文档
scripts/                 # 辅助脚本
```

## 文档

- [项目总方案](./project-docs/01-project-overview.md)
- [数据库设计](./project-docs/02-database-design.md)
- [API 设计](./project-docs/03-api-design.md)
- [项目骨架与开发顺序](./project-docs/04-project-structure.md)
- [宿主机部署方案](./project-docs/05-host-deployment.md)
- [宿主机执行步骤](./project-docs/06-host-deployment-steps.md)
- [Mock 到真实替换路线](./project-docs/07-mock-to-real-plan.md)
- [Migration 规划](./project-docs/08-migration-plan.md)
- [Webman 真接入准备](./project-docs/09-webman-integration-plan.md)

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
- 前端最小骨架（统一登录 / 工作台 / 题目列表 / 搜题日志）
- 前端统一布局、认证状态、动态菜单预留和管理页骨架

## 开发原则

- 先做最小可上线闭环
- 按生产级思路推进，不只写 demo
- 先后端主链路，再补前端页面
- ES 只做搜索索引，不做主存储
- Redis 只做缓存、配额、限流，不做正式配置库
- 所有生产配置走 env
- 宿主机部署统一用 Nginx + systemd
- 简单运维脚本保留 shell，复杂巡检优先 Python
- mock 数据只是过渡，最终以真实存储接管
