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
- config/database.php
- config/redis.php
- config/mongodb.php
- config/elasticsearch.php
- config/jwt.php
- .env.production.example
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
- DashboardController / DashboardService
- ApiKeyController / ApiKeyService
- BillingController
- LogController
- DocController
- CollectController
- 管理端 User / Role / Permission / Menu / Plan / Announcement / SearchLog 控制器
- 管理端 Doc / Collect / ApiSource / SystemConfig 控制器
- 管理端 Question / QuestionCategory / QuestionType / QuestionSource / QuestionTag 控制器
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版（已收敛为统一用户体系）
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表/详情/更新/删除骨架
- 结构收束与真接入准备文档

## 当前状态
当前代码已进入 **第一批真接入执行准备阶段**：
- 真实 composer 依赖清单已收口
- 宿主机后端依赖准备脚本已补齐
- 真接入批次与首批替换目标已明确
- 下一步可以开始执行 auth/rbac 主线的真实替换准备

## 真接入参考文档
- `project-docs/10-backend-real-dependencies.md`
- `project-docs/11-integration-batches.md`
- `project-docs/12-first-real-targets.md`
- `project-docs/13-first-integration-execution-checklist.md`
- `project-docs/14-host-backend-prep.md`
