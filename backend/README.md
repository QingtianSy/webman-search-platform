# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 已切换为真实依赖占位版
- start.php 已收束为可替换启动占位层
- bootstrap/app.php
- bootstrap/routes.php
- config/plugin/README.md
- public/README.md
- public/index.php 占位入口
- support/Request.php 已收束为兼容请求占位层
- support/ApiResponse.php 已收束为兼容响应占位层
- database/migrations/ 首批 auth/rbac SQL 草案
- database/seeds/ 首批 auth/rbac seed 草案
- PasswordService 已抽出
- auth/rbac Repository 已支持 mock/real 双模式占位
- 其余业务骨架与真接入文档已齐备

## 当前状态
当前代码已进入 **auth / rbac real 分支实现骨架阶段**：
- `AUTH_RBAC_SOURCE` 已可切 mock / real
- `*Real()` 分支已作为真实查询占位落点
- 下一步只需要把 real 分支中的伪查询替换为真实 DB 查询即可

## 关键文档
- `project-docs/22-auth-rbac-repository-swap-plan.md`
- `project-docs/23-auth-rbac-real-query-notes.md`
