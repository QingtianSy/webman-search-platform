# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 已切换为真实依赖占位版
- start.php 启动入口骨架
- bootstrap/app.php
- bootstrap/routes.php
- config/app.php
- config/routes.php
- config/middleware.php
- config/plugin/README.md
- config/database.php
- config/redis.php
- config/mongodb.php
- config/elasticsearch.php
- config/jwt.php
- .env.production.example
- public/README.md
- support/helpers.php
- support/Request.php
- support/ResponseCode.php
- support/ApiResponse.php
- support/ClientFactory.php
- support/Pagination.php
- support/adapter/* 适配层骨架
- BusinessException
- ExceptionHandler
- RequestIdMiddleware
- User/Admin/Open 鉴权中间件
- 统一认证控制器 auth/AuthController
- 业务骨架与管理操作接口多轮补齐
- 结构收束与真接入准备文档

## 当前状态
当前代码已进入 **批次 1 真接入文件准备阶段**：
- 占位文件与真实替换目标映射已经明确
- `public/` 与 `config/plugin/` 目录已提前准备
- 下一步可进入真实 Webman 入口与 Request/Response 替换准备

## 关键文档
- `project-docs/15-placeholder-replacement-map.md`
- `project-docs/16-webman-file-prep.md`
- `project-docs/13-first-integration-execution-checklist.md`
- `project-docs/14-host-backend-prep.md`
