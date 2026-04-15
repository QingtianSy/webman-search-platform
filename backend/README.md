# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 骨架（后续将替换为真实依赖清单）
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
当前代码已进入 **真接入前收口阶段**：
- 业务骨架、页面骨架、权限模型、操作接口已经比较完整
- 下一步重点不是继续堆 mock，而是按既定顺序替换为真实依赖和真实存储
- 关键参考文档：
  - `project-docs/07-mock-to-real-plan.md`
  - `project-docs/08-migration-plan.md`
  - `project-docs/09-webman-integration-plan.md`
  - `project-docs/10-backend-real-dependencies.md`
  - `project-docs/11-integration-batches.md`
  - `project-docs/12-first-real-targets.md`
