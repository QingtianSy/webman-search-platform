# Mock → Real 替换路线

## 一、当前状态
当前项目使用 `backend/storage/mock/*.json` 作为过渡数据源，目的是：
- 先把业务结构和接口骨架搭完整
- 在不依赖真实数据库安装的情况下推进功能设计
- 为后续真实接入 Webman + MySQL + MongoDB + Elasticsearch + Redis 做准备

## 二、替换顺序建议

### 第一步：统一认证
先替换：
- `UserRepository`
- `RoleRepository`
- `PermissionRepository`
- `UserRoleRepository`
- `RolePermissionRepository`
- `MenuRepository`

目标：
- `users` / `roles` / `permissions` / `user_role` / `role_permission` / `menus` 改为真实 MySQL 表
- 统一登录改成真实数据库登录

### 第二步：题库主线
替换：
- `QuestionRepository`
- `QuestionService`

目标：
- 完整题目改存 MongoDB
- 搜题主检索改走 Elasticsearch
- mock questions.json 下线

### 第三步：日志与额度
替换：
- `SearchLogRepository`
- `SearchLogDetailRepository`
- `QuotaService`

目标：
- 搜题日志主表入 MySQL
- 搜题日志明细入 MongoDB
- 额度缓存与扣次入 Redis

### 第四步：用户中心
替换：
- `WalletRepository`
- `SubscriptionRepository`
- `ApiKeyRepository`
- `AnnouncementRepository`

目标：
- 钱包、套餐、API Key、公告改为真实 MySQL 数据

### 第五步：采集与配置
替换：
- `CollectAccountRepository`
- `CollectTaskRepository`
- `CollectTaskDetailRepository`
- `ApiSourceRepository`
- `SystemConfigRepository`
- `DocArticleRepository`
- `DocCategoryRepository`
- `DocConfigRepository`

目标：
- 采集与配置改为真实 MySQL/Mongo 持久化

## 三、替换原则
- 先保持接口层不变，只替换 Repository 实现
- Service 尽量不大改，减少业务回归风险
- 每替换一个模块，就删除对应的 mock 数据文件依赖
- 替换后补 migration 与 seed

## 四、推荐顺序总结
1. auth / rbac
2. question / search
3. logs / quota
4. user-center
5. collect / docs / config
