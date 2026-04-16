# public

当前项目以 `start.php` 作为 Webman 主入口，按官方方式运行。

## 当前说明
- `public/` 目录仍保留，用于静态资源与站点运行目录
- 后续宝塔/Nginx 配置时，Webman 主体进程仍由 `php start.php start` 启动
- 当前项目不是传统 PHP-FPM 入口模式，因此 `public/index.php` 不作为主要启动入口
