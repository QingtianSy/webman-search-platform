# 真接入执行顺序总清单

## 一、执行原则
1. **先认证，后业务**：先接 auth/rbac，再接题库与搜题主线。
2. **先替换 Repository，后看是否需要调整 Service**。
3. **接口路径和返回结构尽量不变**。
4. **每完成一批，先做回归，再进入下一批**。
5. **mock 文件只在对应 real 能力稳定后再删除**。

---

## 二、总批次顺序

### Phase 0：环境确认
- PHP 8.2
- Composer 2.x
- Swoole 扩展
- Redis 扩展
- Mongo 扩展
- MySQL 可用
- MongoDB 可用
- Elasticsearch 可用

### Phase 1：框架与基础设施
- `composer install`
- 确认 `backend/composer.json` 依赖可用
- 保留当前目录结构
- 准备真实 Webman 启动接管点

### Phase 2：auth / rbac 真替换
- 建表：users / roles / permissions / user_role / role_permission / menus
- 导入 seed
- 切 `AUTH_RBAC_SOURCE=real`
- 替换以下类的 real 分支：
  - UserRepository
  - RoleRepository
  - PermissionRepository
  - UserRoleRepository
  - RolePermissionRepository
  - MenuRepository
- 验证：
  - `/api/v1/auth/login`
  - `/api/v1/auth/profile`
  - `/api/v1/auth/menus`
  - `/api/v1/auth/permissions`

### Phase 3：question / search 真替换
- 建 questions 集合 / question_index 索引 / search_logs 表 / search_log_details 集合
- 切 `QUESTION_SOURCE=real`
- 切 `LOG_SOURCE=real`
- 替换以下类的 real 分支：
  - QuestionRepository
  - SearchLogRepository
  - SearchLogDetailRepository
  - QuotaService
- 调整 SearchService 为：
  - ES 命中
  - Mongo 回查
  - MySQL/Mongo 记日志
  - Redis 配额
- 验证：
  - `/api/v1/user/search/query`
  - `/api/v1/user/search/logs`
  - `/open/v1/search/query`
  - `/open/v1/quota/detail`

### Phase 4：用户中心真替换
- 钱包
- 套餐
- API Key
- 公告
- 工作台 overview

### Phase 5：管理端配置与文档真替换
- system_configs
- docs_articles / docs_categories
- api_sources
- announcements

### Phase 6：采集中心真替换
- collect_accounts
- collect_tasks
- collect_task_details / 原始结果
- 等待你补充核心采集脚本后再接执行链

---

## 三、每批次验证清单

### auth / rbac
- 登录成功
- 非法密码失败
- 管理员有 admin.access
- 普通用户无 admin.access
- menus 与 permissions 返回符合预期

### question / search
- 题目列表正常
- 搜题能命中
- 搜题日志落库
- quota 能扣减
- open API 能返回答案

### user-center
- API Key 列表正常
- 钱包与套餐正常
- 工作台卡片数据正常

---

## 四、执行建议
- **先不要同时改 auth 和 search**
- **先打通统一登录，再处理搜索**
- **搜索主线接通后，再进入用户中心与配置模块**

---

## 五、当前最推荐下一步
### 直接执行：
1. 宿主机准备 composer 依赖
2. auth/rbac 建表与 seed
3. `AUTH_RBAC_SOURCE=real`
4. 验证统一登录主线
