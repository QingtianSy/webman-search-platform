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
- 其余业务骨架与真接入文档已齐备
- database/migrations/ 目录已预留
- `0001_auth_rbac_schema.sql` 已提供首批 SQL 草案

## 当前状态
当前代码已进入 **auth / rbac 真替换草案阶段**：
- 统一认证与权限模型的真实替换顺序已明确
- 首批 users / roles / permissions / menus SQL 草案已落地
- 下一步可以在宿主机环境确认后进入真实表创建与 Repository 替换

## 关键文档
- `project-docs/17-auth-rbac-replacement-plan.md`
- `project-docs/18-auth-rbac-schema-draft.md`
- `backend/database/migrations/0001_auth_rbac_schema.sql`
