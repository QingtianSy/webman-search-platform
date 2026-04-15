# 官方目录结构进一步对齐说明

## 本轮动作
1. 恢复 `support/Request.php` 为主兼容层
2. `support/InputRequest.php` 改为兼容别名层
3. 新增 `support/Response.php`
4. 新增 `support/bootstrap.php`
5. 补齐一批更贴官方 Webman 的 `config/*` 占位文件：
   - autoload.php
   - bootstrap.php
   - container.php
   - dependence.php
   - exception.php
   - log.php
   - process.php
   - route.php
   - server.php
   - view.php
   - static.php
   - translation.php
   - session.php

## 结论
这一步不是推倒当前项目，而是让当前项目的 backend 更靠近官方骨架，同时保留业务扩展层（service / repository / validate）。
