# Mock → Real 执行补充（auth/rbac）

## 当前新增文档
- `project-docs/17-auth-rbac-replacement-plan.md`
- `project-docs/18-auth-rbac-schema-draft.md`
- `project-docs/19-auth-rbac-field-mapping.md`
- `backend/database/migrations/0001_auth_rbac_schema.sql`
- `backend/database/seeds/0001_auth_rbac_seed.sql`

## 推荐最小替换顺序
1. 创建 auth/rbac 表
2. 导入基础 seed
3. 将 `UserRepository` 改查真实 `users`
4. 将 `RoleRepository` / `PermissionRepository` / `UserRoleRepository` / `RolePermissionRepository` / `MenuRepository` 改查真实表
5. 验证：
   - `/api/v1/auth/login`
   - `/api/v1/auth/profile`
   - `/api/v1/auth/menus`
   - `/api/v1/auth/permissions`
