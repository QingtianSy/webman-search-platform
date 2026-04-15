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

## 当前状态
当前代码已进入 **批次 1 真接入代码级准备阶段**：
- 占位启动入口、请求层、响应层、bootstrap 层都已标明未来替换边界
- `public/` 入口预留已创建
- 下一步可开始按批次替换 auth / rbac 的真实实现
