# API 设计

## 一、命名空间

### 用户端
- /api/v1/user/*

### 管理端
- /api/v1/admin/*

### 开放平台
- /open/v1/*

## 二、统一返回结构

```json
{
  "code": 1,
  "msg": "success",
  "data": {},
  "request_id": "20260415xxxx"
}
```

## 三、用户端核心接口

### 鉴权
- POST /api/v1/user/auth/login
- GET /api/v1/user/auth/profile
- POST /api/v1/user/auth/logout
- POST /api/v1/user/auth/change-password
- GET /api/v1/user/auth/captcha

### 工作台 / 用户中心
- GET /api/v1/user/dashboard/overview
- GET /api/v1/user/profile/detail
- PUT /api/v1/user/profile/update

### 套餐 / 钱包 / 配额
- GET /api/v1/user/plan/list
- GET /api/v1/user/plan/current
- GET /api/v1/user/wallet/detail
- GET /api/v1/user/wallet/logs
- POST /api/v1/user/order/create
- GET /api/v1/user/order/list
- GET /api/v1/user/payment/list
- GET /api/v1/user/quota/detail
- GET /api/v1/user/quota/logs

### API Key / 白名单
- GET /api/v1/user/api-key/list
- POST /api/v1/user/api-key/create
- POST /api/v1/user/api-key/toggle
- DELETE /api/v1/user/api-key/delete
- GET /api/v1/user/ip-whitelist/list
- POST /api/v1/user/ip-whitelist/create
- DELETE /api/v1/user/ip-whitelist/delete

### 搜题
- POST /api/v1/user/search/query
- POST /api/v1/user/search/batch
- GET /api/v1/user/search/logs
- GET /api/v1/user/search/log/detail

### 采集
- GET /api/v1/user/collect/account/list
- POST /api/v1/user/collect/account/create
- PUT /api/v1/user/collect/account/update
- DELETE /api/v1/user/collect/account/delete
- GET /api/v1/user/collect/task/list
- POST /api/v1/user/collect/task/create
- GET /api/v1/user/collect/task/detail
- GET /api/v1/user/collect/task/courses
- POST /api/v1/user/collect/task/stop

### 文档 / 公告 / 日志
- GET /api/v1/user/doc/category/list
- GET /api/v1/user/doc/article/detail
- GET /api/v1/user/doc/config
- GET /api/v1/user/log/balance
- GET /api/v1/user/log/search
- GET /api/v1/user/log/payment
- GET /api/v1/user/log/operate
- GET /api/v1/user/log/login

## 四、管理端核心接口

### 鉴权
- POST /api/v1/admin/auth/login
- GET /api/v1/admin/auth/profile
- GET /api/v1/admin/auth/menus
- GET /api/v1/admin/auth/permissions

### 题库
- GET /api/v1/admin/question/list
- GET /api/v1/admin/question/detail
- POST /api/v1/admin/question/create
- PUT /api/v1/admin/question/update
- DELETE /api/v1/admin/question/delete
- POST /api/v1/admin/question/batch-delete
- GET /api/v1/admin/question/export
- POST /api/v1/admin/question/import

### 基础字典
- /api/v1/admin/question-category/*
- /api/v1/admin/question-type/*
- /api/v1/admin/question-source/*
- /api/v1/admin/question-tag/*

### 采集
- GET /api/v1/admin/collect/task/list
- GET /api/v1/admin/collect/task/errors
- POST /api/v1/admin/collect/task/retry
- GET /api/v1/admin/collect/raw/list

### 接口源配置
- GET /api/v1/admin/api-source/list
- GET /api/v1/admin/api-source/detail
- POST /api/v1/admin/api-source/create
- PUT /api/v1/admin/api-source/update
- POST /api/v1/admin/api-source/toggle
- DELETE /api/v1/admin/api-source/delete
- POST /api/v1/admin/api-source/test
- GET /api/v1/admin/api-source-param/list
- POST /api/v1/admin/api-source-param/create
- PUT /api/v1/admin/api-source-param/update
- DELETE /api/v1/admin/api-source-param/delete

### 日志与运营
- GET /api/v1/admin/log/login/list
- GET /api/v1/admin/log/operate/list
- GET /api/v1/admin/log/search/list
- GET /api/v1/admin/log/search/detail
- GET /api/v1/admin/log/api/list
- GET /api/v1/admin/log/collect/list
- GET /api/v1/admin/log/error/list
- GET /api/v1/admin/user/list
- GET /api/v1/admin/user/detail
- POST /api/v1/admin/user/create
- PUT /api/v1/admin/user/update
- POST /api/v1/admin/user/toggle
- POST /api/v1/admin/user/reset-password
- GET /api/v1/admin/plan/list
- POST /api/v1/admin/plan/create
- PUT /api/v1/admin/plan/update
- DELETE /api/v1/admin/plan/delete
- GET /api/v1/admin/order/list
- GET /api/v1/admin/order/detail
- GET /api/v1/admin/wallet/list
- POST /api/v1/admin/wallet/manual-adjust
- GET /api/v1/admin/announcement/list
- POST /api/v1/admin/announcement/create
- PUT /api/v1/admin/announcement/update
- DELETE /api/v1/admin/announcement/delete
- GET /api/v1/admin/system-config/list
- POST /api/v1/admin/system-config/update

## 五、开放平台
- POST /open/v1/search/query
- GET /open/v1/quota/detail
- GET /open/v1/health
