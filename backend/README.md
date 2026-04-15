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
- DashboardController / DashboardService
- ApiKeyController / ApiKeyService
- BillingController
- LogController
- DocController
- CollectController
- 管理端 User / Plan / Announcement / SearchLog 控制器
- 管理端 Doc / Collect / ApiSource 控制器
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表骨架
- WalletRepository / SubscriptionRepository / AnnouncementRepository
- BalanceLogRepository / PaymentLogRepository / LoginLogRepository / OperateLogRepository
- DocCategoryRepository / DocArticleRepository
- CollectAccountRepository / CollectTaskRepository / ApiSourceRepository
- SearchQueryValidator
- Repository 骨架与 API Key 仓储骨架
- HealthService 健康检查服务
- storage/mock 用户、题目、钱包、套餐、API Key、公告、日志、文档、采集、接口源数据源
- storage/logs 本地日志落地占位

## 当前状态
当前代码已进入 **文档中心 + 采集中心外围 + 题库配置主线补全阶段**：
- 登录已接入 mock 用户数据源
- 工作台已接入 mock 钱包 / 套餐 / 公告数据
- API Key 列表已接入 mock 数据源
- 钱包详情 / 当前套餐接口已补齐
- 用户日志中心已补齐余额 / 支付 / 登录 / 操作日志接口
- 文档中心已补齐分类与文章详情接口
- 采集中心外围已补齐采集账号列表与采集任务列表接口
- 管理端已补齐用户列表 / 套餐列表 / 公告列表 / 搜题日志列表
- 管理端已补齐文档文章列表 / 采集任务列表 / 接口源列表与详情
- 题目列表已接入 mock 题库数据源
- 搜题接口可基于 mock 题库返回真实样本结果
- 搜题日志会落到本地 jsonl 文件中
- 健康检查与部署脚本仍保留生产级结构

## 当前 mock 账号
### 用户
- username: `demo_user`
- password: `123456`

### 管理员
- username: `admin`
- password: `admin123`

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
- 用数据库替换 mock 用户与题库数据源
- 接入真实日志写入与额度扣减
- 实现题目 CRUD、API Key 管理、套餐、采集任务与日志列表持久化
- 等待补充采集核心脚本后接入真实采集执行链
