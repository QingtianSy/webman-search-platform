# backend

Webman 后端代码目录。

## 当前状态
当前 backend 已进入 **轻量质量门禁可持续执行阶段**：
- `composer smoke` 可快速验证关键主线
- `scripts/check_docs_clean.sh` 可快速发现文档污染
- `scripts/check_backend_routes.sh` 可快速验证路由目标存在性

## 当前建议
在 Minis 环境中，优先使用轻量门禁，不再频繁执行全仓库逐文件 lint。
