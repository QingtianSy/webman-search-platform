# backend

Webman 后端代码目录。

## 当前状态
当前 backend 已进一步按官方 Webman 习惯收口：
- 业务代码主引用已收回 `support/Request`
- `support/InputRequest` 仅保留为兼容别名层
- 官方命名习惯优先于自定义命名
- 当前收口后 PHP lint 与 smoke tests 已通过

## 关键点
后续如果继续改后端，优先沿用官方 `support/Request.php` 命名与用法。
