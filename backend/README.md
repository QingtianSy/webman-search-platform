# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **Phase 1 真实依赖接入准备阶段**：
- `composer.json` 已收敛为真实依赖声明
- `public/index.php` 已能在无依赖时给出明确提示
- `start.php` 已能在无依赖时给出明确提示
- `scripts/check_backend_runtime.sh` 已可用于宿主机运行时前置检查
- 下一步一旦开始执行，就可以先完成 composer install，再进入 auth/rbac 真实替换

## 关键文档
- `project-docs/26-phase1-runtime-execution.md`
