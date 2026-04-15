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
- 其余业务骨架与真接入文档已齐备

## 当前状态
当前代码已进入 **auth / rbac 真替换准备加强阶段**：
- 表结构草案已齐
- seed 草案已齐
- 字段映射文档已齐
- 下一步可以进入 Repository 层真实替换准备
