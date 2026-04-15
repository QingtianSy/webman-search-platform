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
- auth/rbac Repository 已补齐 real-ready 注释
- 其余业务骨架与真接入文档已齐备

## 当前状态
当前代码已进入 **auth / rbac 代码替换准备阶段**：
- 密码校验已从 AuthService 中抽离
- Repository 边界已收稳
- 下一步可以优先替换 users / roles / permissions / menus 的真实实现

## 关键文档
- `project-docs/17-auth-rbac-replacement-plan.md`
- `project-docs/18-auth-rbac-schema-draft.md`
- `project-docs/19-auth-rbac-field-mapping.md`
- `project-docs/20-auth-rbac-execution-note.md`
- `project-docs/21-auth-rbac-code-prep.md`
