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
- auth/rbac Repository 已进入 mock/real 双模式准备
- 其余业务骨架与真接入文档已齐备

## 当前状态
当前代码已进入 **auth / rbac Repository 双模式切换准备阶段**：
- 通过 `config/integration.php` 中的 `AUTH_RBAC_SOURCE` 控制 mock / real
- Repository 对外方法签名保持不变
- 下一步可逐个在 real 分支中接真实 SQL 查询
