# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **Phase 1 真实依赖接入可执行阶段**：
- `composer.json` 已修正为可解析的真实依赖声明
- 当前环境已验证 `composer install --dry-run` 可通过
- 当前环境已验证 `mongodb` 扩展可用
- 下一步在宿主机上可直接执行依赖准备，再进入 auth/rbac 真接入

## 关键文档
- `project-docs/26-phase1-runtime-execution.md`
