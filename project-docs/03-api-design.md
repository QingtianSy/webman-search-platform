# API 设计（统一认证主入口版）

## 一、主认证入口
### 统一认证接口（推荐）
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/profile`
- `GET /api/v1/auth/menus`
- `GET /api/v1/auth/permissions`

说明：
- 当前项目采用统一用户体系
- 所有账号归于 `users`
- 用户端与管理端通过 `roles / permissions / menus` 区分
- 前端登录后按 `default_portal` 决定默认进入区域

## 二、兼容入口（保留但非主方案）
以下接口仅为兼容过渡保留，不作为当前推荐主方案：
- `POST /api/v1/user/auth/login`
- `POST /api/v1/admin/auth/login`

## 三、关键用户端接口
- `GET /api/v1/user/dashboard/overview`
- `GET /api/v1/user/api-key/list`
- `GET /api/v1/user/wallet/detail`
- `GET /api/v1/user/plan/current`
- `POST /api/v1/user/search/query`
- `GET /api/v1/user/search/logs`
- `GET /api/v1/user/doc/category/list`
- `GET /api/v1/user/doc/article/detail`
- `GET /api/v1/user/doc/config`
- `GET /api/v1/user/collect/task/list`
- `GET /api/v1/user/collect/task/detail`

## 四、关键管理端接口
- `GET /api/v1/admin/question/list`
- `GET /api/v1/admin/question/detail`
- `POST /api/v1/admin/question/create`
- `PUT /api/v1/admin/question/update`
- `DELETE /api/v1/admin/question/delete`
- `GET /api/v1/admin/role/list`
- `GET /api/v1/admin/permission/list`
- `GET /api/v1/admin/menu/list`
- `GET /api/v1/admin/system-config/list`
- `POST /api/v1/admin/system-config/update`
- `GET /api/v1/admin/doc/article/list`
- `POST /api/v1/admin/doc/article/create`
- `PUT /api/v1/admin/doc/article/update`
- `DELETE /api/v1/admin/doc/article/delete`
- `GET /api/v1/admin/collect/task/list`
- `GET /api/v1/admin/collect/task/detail`
- `POST /api/v1/admin/collect/task/stop`
- `POST /api/v1/admin/collect/task/retry`
- `GET /api/v1/admin/api-source/list`
- `GET /api/v1/admin/api-source/detail`
- `POST /api/v1/admin/api-source/test`

## 五、开放平台
- `POST /open/v1/search/query`
- `GET /open/v1/quota/detail`
- `GET /open/v1/health`
