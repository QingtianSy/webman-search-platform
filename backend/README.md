# backend

Webman 后端代码目录。

## 当前状态
当前 backend 已完成关键一步：
- 主路由入口已从自定义数组式 `config/routes.php` 切换为更贴官方的 `config/route.php + Webman\\Route` 注册方式
- 当前后端结构已更接近真正的官方 Webman 运行态

## 当前建议
后续继续改后端时，优先改 `config/route.php`，不要再把 `config/routes.php` 当主入口。
