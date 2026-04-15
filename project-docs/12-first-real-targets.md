# 首批真实替换目标清单

## 第一优先级
### 统一认证
- `AuthService`
- `UserRepository`
- `RoleRepository`
- `PermissionRepository`
- `UserRoleRepository`
- `RolePermissionRepository`
- `MenuRepository`

### 搜题主线
- `QuestionRepository`
- `QuestionService`
- `SearchService`
- `SearchLogRepository`
- `SearchLogDetailRepository`
- `QuotaService`

## 第二优先级
### 用户中心
- `WalletRepository`
- `SubscriptionRepository`
- `ApiKeyRepository`
- `AnnouncementRepository`

### 管理基础字典
- `QuestionCategoryRepository`
- `QuestionTypeRepository`
- `QuestionSourceRepository`
- `QuestionTagRepository`

## 第三优先级
### 配置与采集
- `SystemConfigRepository`
- `DocArticleRepository`
- `DocCategoryRepository`
- `DocConfigRepository`
- `CollectAccountRepository`
- `CollectTaskRepository`
- `CollectTaskDetailRepository`
- `ApiSourceRepository`

## 替换要求
- 先保留接口不变
- 先替换 Repository，再调整 Service
- 替换完成后删除对应 mock 文件依赖
