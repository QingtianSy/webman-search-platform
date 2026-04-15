# 项目骨架与开发顺序

## 一、后端目录

```bash
backend/
├── app/
│   ├── controller/
│   │   ├── admin/
│   │   ├── user/
│   │   └── open/
│   ├── service/
│   │   ├── auth/
│   │   ├── question/
│   │   ├── search/
│   │   ├── quota/
│   │   ├── billing/
│   │   ├── collect/
│   │   ├── document/
│   │   └── system/
│   ├── repository/
│   │   ├── mysql/
│   │   ├── mongo/
│   │   ├── es/
│   │   └── redis/
│   ├── model/
│   │   ├── mysql/
│   │   └── mongo/
│   ├── middleware/
│   ├── validate/
│   ├── exception/
│   ├── process/
│   ├── queue/
│   └── common/
├── config/
├── public/
├── runtime/
├── support/
├── tests/
└── composer.json
```

## 二、前端目录

```bash
frontend/
├── src/
│   ├── api/
│   ├── views/
│   │   ├── dashboard/
│   │   ├── profile/
│   │   ├── package/
│   │   ├── question/
│   │   ├── collect/
│   │   ├── api-doc/
│   │   ├── logs/
│   │   └── system/
│   ├── components/
│   ├── stores/
│   ├── router/
│   ├── hooks/
│   ├── utils/
│   └── types/
└── package.json
```

## 三、第一批控制器建议

### 用户端
- User\\AuthController
- User\\DashboardController
- User\\SearchController
- User\\QuotaController
- User\\ApiKeyController

### 管理端
- Admin\\AuthController
- Admin\\QuestionController
- Admin\\QuestionCategoryController
- Admin\\QuestionTypeController
- Admin\\QuestionSourceController
- Admin\\SearchLogController

### 开放平台
- Open\\SearchController
- Open\\HealthController

## 四、第一批 Service 建议
- AuthService
- JwtService
- QuestionService
- QuestionIndexService
- SearchService
- QuotaService
- SearchLogService
- UserService

## 五、第一批 Repository 建议
### MySQL
- UserRepository
- AdminRepository
- PlanRepository
- SubscriptionRepository
- SearchLogRepository

### MongoDB
- QuestionRepository
- SearchLogDetailRepository

### ES
- QuestionIndexRepository

### Redis
- QuotaCacheRepository
- TokenCacheRepository
- RateLimitRepository

## 六、开发顺序

### 第 1 周
- 建后端项目骨架
- 接入 MySQL / MongoDB / ES / Redis
- 统一返回结构
- 全局异常处理
- JWT 中间件
- 健康检查接口

### 第 2 周
- 管理端登录
- 用户端登录
- 题目列表/新增/编辑
- Mongo 入库
- ES 同步

### 第 3 周
- 搜题接口
- Redis 扣次
- 搜题日志
- 用户工作台
- 搜题日志页

### 第 4 周
- API Key
- 套餐/额度
- 文档中心只读
- 最小用户中心
