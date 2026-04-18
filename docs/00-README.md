# Webman Search Platform - 项目文档

## 项目简介

**Webman Search Platform** 是一个基于 Webman + Swoole 的高性能题库搜题 SaaS 平台。

### 核心特性

- 🚀 **高性能**：基于 Swoole 常驻内存，QPS 可达 10000+
- 🔍 **智能搜索**：Elasticsearch 全文检索，毫秒级响应
- 📊 **海量题库**：MongoDB 存储，支持千万级题目
- 🔐 **安全可靠**：JWT 认证 + RBAC 权限控制
- 💰 **商业化**：套餐订阅 + API 开放平台
- 📱 **现代化前端**：Vue 3 + Vben Admin + Naive UI

### 技术栈

**后端**
- PHP 8.2
- Webman 2.2 + Workerman 5.1 + Swoole 5.1
- MySQL 8.0 + MongoDB 7 + Elasticsearch 8 + Redis 7.2

**前端**
- Vue 3 + TypeScript 5
- Vben Admin 5.7 + Naive UI
- Vite 5 + Pnpm

**部署**
- Ubuntu 22.04 LTS
- Nginx + Systemd
- 宿主机直装（非 Docker）

### 项目定位

- **目标用户**：教育机构、培训机构、在线教育平台
- **核心价值**：提供高性能、低成本的题库搜题解决方案
- **商业模式**：SaaS 订阅 + API 调用计费

## 文档导航

### 产品与需求
- [01-产品需求文档.md](./01-产品需求文档.md) - PRD，产品定位、用户画像、核心功能
- [02-功能规格说明.md](./02-功能规格说明.md) - 详细功能清单与业务流程

### 技术架构
- [03-系统架构设计.md](./03-系统架构设计.md) - 整体架构、分层设计、技术选型
- [04-技术选型说明.md](./04-技术选型说明.md) - 技术栈选型理由与对比
- [05-数据库设计.md](./05-数据库设计.md) - MySQL/MongoDB/ES/Redis 设计
- [06-API接口文档.md](./06-API接口文档.md) - RESTful API 设计规范

### 前端开发
- [07-前端架构设计.md](./07-前端架构设计.md) - 前端技术架构与目录结构

### 运维部署
- [08-部署运维文档.md](./08-部署运维文档.md) - 生产环境部署指南

### 开发规范
- [09-开发规范.md](./09-开发规范.md) - 代码规范、Git 规范、测试规范

### 项目管理
- [10-项目路线图.md](./10-项目路线图.md) - 开发计划与里程碑

## 快速开始

### 后端开发

```bash
cd backend
composer install
cp .env.example .env
# 配置数据库连接
php start.php start
```

### 前端开发

```bash
cd frontend
pnpm install
pnpm dev:naive
```

### 访问地址

- 后端 API：http://localhost:8787
- 前端界面：http://localhost:5173
- API 文档：http://localhost:8787/docs

## 项目状态

- ✅ **Phase 1**：基础架构搭建完成
- ✅ **Phase 2**：核心功能开发完成（Mock 数据）
- 🚧 **Phase 3**：真实数据库接入（进行中）
- ⏳ **Phase 4**：生产环境部署（待开始）

## 团队协作

### 分支管理
- `main` - 生产分支
- `develop` - 开发分支
- `feature/*` - 功能分支
- `hotfix/*` - 紧急修复分支

### 提交规范
```
feat: 新功能
fix: 修复 bug
refactor: 重构
docs: 文档更新
style: 代码格式
test: 测试相关
chore: 构建/工具相关
```

## 联系方式

- 项目地址：https://github.com/QingtianSy/webman-search-platform
- 问题反馈：https://github.com/QingtianSy/webman-search-platform/issues

## 许可证

Proprietary - 专有软件
