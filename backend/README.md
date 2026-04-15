# backend

Webman 后端代码目录。

## 当前状态
当前 backend 已进一步向官方 Webman 目录结构收口：
- `support/Request.php` 已恢复为主兼容层
- `support/InputRequest.php` 保留为过渡兼容别名
- 已补 `support/Response.php` 与 `support/bootstrap.php`
- 已补一批官方推荐的 `config/*` 占位文件
- 在不推倒当前业务扩展层的前提下，后端骨架已明显更接近官方结构

## 关键文档
- `project-docs/45-webman-structure-further-alignment.md`
