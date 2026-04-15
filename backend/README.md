# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **auth / rbac real 查询骨架阶段**：
- `AUTH_RBAC_SOURCE` 可切 `mock | real`
- `UserRepository` / `RoleRepository` / `PermissionRepository` / `UserRoleRepository` / `RolePermissionRepository` / `MenuRepository` 的 `*Real()` 已补为真实 PDO 查询骨架
- `PasswordService` 已抽出，可后续平滑切换到 password_hash
- 下一步只需要在宿主机环境可用后，真实执行 migration + seed + 打开 real 配置即可开始第一批认证主线替换验证

## 当前建议验证顺序（真实接入后）
1. `/api/v1/auth/login`
2. `/api/v1/auth/profile`
3. `/api/v1/auth/menus`
4. `/api/v1/auth/permissions`
