# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 骨架
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
- 管理端 User / Plan / Announcement / SearchLog 控制器
- 管理端 Doc / Collect / ApiSource / SystemConfig 控制器
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版（已收敛为统一用户体系）
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表骨架
- WalletRepository / SubscriptionRepository / AnnouncementRepository
- BalanceLogRepository / PaymentLogRepository / LoginLogRepository / OperateLogRepository
- DocCategoryRepository / DocArticleRepository / DocConfigRepository
- CollectAccountRepository / CollectTaskRepository / CollectTaskDetailRepository / ApiSourceRepository
- RoleRepository / PermissionRepository / UserRoleRepository / RolePermissionRepository / MenuRepository
- SystemConfigRepository
- SearchQueryValidator
- Repository 骨架与 API Key 仓储骨架
- HealthService 健康检查服务
- storage/mock 用户、角色、权限、菜单、题目、钱包、套餐、API Key、公告、日志、文档、采集、接口源、系统配置数据源
- storage/logs 本地日志落地占位

## 当前状态
当前代码已进入 **统一用户体系收敛阶段**：
- 采用一个 `users` 体系，通过角色和权限区分用户端与管理端
- 新增统一认证入口：`/api/v1/auth/login`
- 统一登录返回 user / roles / permissions / menus / default_portal
- 保留 `/api/v1/user/auth/*` 与 `/api/v1/admin/auth/*` 作为兼容入口
- 用户端、管理端与开放平台模块继续沿用同一套用户身份体系

## 当前 mock 账号
### 用户
- username: `demo_user`
- password: `123456`
- roles: `user`

### 管理员
- username: `admin`
- password: `admin123`
- roles: `admin`

### 开放平台 API Key
- api_key: `ak_demo_001`
- api_secret: `sk_demo_001`

## 采集执行链说明
当前采集模块只做到外围结构与任务数据模型，执行链保留为：
- `runner_script`: `pending://collect-core-script`
- `next_script`: `pending://post-collect-handler`

等待你后续补充核心采集脚本后，再接真实采集执行流。

## 下一步
- 接入真实 Webman 官方框架
- 接入真实 JWT 实现
- 接入 MySQL / MongoDB / ES / Redis 客户端
- 用数据库替换 mock 用户与角色权限题库数据源
- 接入真实日志写入与额度扣减
- 实现题目 CRUD、API Key 管理、套餐、采集任务与日志列表持久化
- 等待补充采集核心脚本后接入真实采集执行链
