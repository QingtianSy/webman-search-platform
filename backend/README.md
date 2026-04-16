# backend

Webman 后端代码目录。

## 当前状态
当前 backend 的启动主入口已进一步按官方 Webman 写法收口：
- `start.php` 现在直接 `require vendor/autoload.php` + `support/bootstrap.php` + `support\\App::run()`
- `support/bootstrap.php` 现在直接对齐官方框架 bootstrap
- 当前项目后端已不再使用 `public/index.php` 作为主入口

## 部署提示
后续在宝塔中启动 Webman 主体进程，优先使用：
```bash
php /www/wwwroot/search-platform/backend/start.php start
```
