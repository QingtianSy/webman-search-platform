# backend

Webman 后端代码目录。

## 当前状态
当前 backend 已进一步严格按官方 Webman 习惯收口：
- 已删除本地自定义 `support/Request.php` / `InputRequest.php` / `Response.php`
- 业务代码现在直接使用官方 `support\\Request`
- composer dump-autoload 与 smoke tests 已通过
- 这一步意味着后端请求层已不再自定义兼容，后续真接入更干净
