# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **auth / rbac 真实替换推进阶段**：
- `UserRepository::findById()` 已补齐 mock / real 双模式
- `RoleRepository::findByIds()` 已具备真实查询分支
- `AuthService` 已收束出 `buildAuthPayload()` 统一组装逻辑
- `PasswordService` 已负责密码兼容校验
- 现在 auth/rbac 主线更适合逐步切真实表，而不是继续扩大 mock 逻辑

## 关键文档
- `project-docs/28-auth-rbac-code-progress.md`
