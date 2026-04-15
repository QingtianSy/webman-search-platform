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
- 管理端 User / Role / Permission / Menu / Plan / Announcement / SearchLog 控制器
- 管理端 Doc / Collect / ApiSource / SystemConfig 控制器
- 管理端 Question / QuestionCategory / QuestionType / QuestionSource / QuestionTag 控制器
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版（已收敛为统一用户体系）
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表/详情/更新/删除骨架
- WalletRepository / SubscriptionRepository / AnnouncementRepository
- BalanceLogRepository / PaymentLogRepository / LoginLogRepository / OperateLogRepository
- DocCategoryRepository / DocArticleRepository / DocConfigRepository
- CollectAccountRepository / CollectTaskRepository / CollectTaskDetailRepository / ApiSourceRepository
- RoleRepository / PermissionRepository / UserRoleRepository / RolePermissionRepository / MenuRepository
- QuestionCategoryRepository / QuestionTypeRepository / QuestionSourceRepository / QuestionTagRepository
- SystemConfigRepository
- SearchQueryValidator
- Repository 骨架与 API Key 仓储骨架
- HealthService 健康检查服务
- storage/mock 用户、角色、权限、菜单、题目分类/题型/来源/标签、题目、钱包、套餐、API Key、公告、日志、文档、采集、接口源、系统配置数据源
- storage/logs 本地日志落地占位

## 当前状态
当前代码已进入 **管理端操作型接口骨架第三轮阶段**：
- 题目管理已补齐 create / update / delete / detail / list
- 公告管理已补齐 create / update / delete
- 文档管理已补齐 create / update / delete
- 系统配置已补齐 update
- 接口源已补齐 test
- 采集任务已补齐 stop / retry
- 用户 API Key 已补齐 delete
- 统一认证、题库管理、开放平台和用户中心结构继续保持统一

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
