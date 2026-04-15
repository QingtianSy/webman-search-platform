## 官方 Webman 目录结构修正结论

基于官方目录结构截图，后端后续应更严格按 Webman 官方骨架收口：
- 保留 `support/Request.php`
- 补充 `support/Response.php`
- 补充 `support/bootstrap.php`
- 更完整地补齐 `config/*` 官方推荐文件占位
- 保留 `service / repository / validate` 作为业务扩展层

结论：
**前端不建议按 Vben monorepo 推倒重来；后端建议更严格按官方 Webman 结构收口。**
