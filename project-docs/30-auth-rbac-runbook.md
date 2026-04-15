# Auth / RBAC 真实接入执行脚本说明

## 当前可用脚本
- `scripts/check_auth_rbac_db.sh`
- `scripts/apply_auth_rbac_schema.sh`
- `scripts/apply_auth_rbac_seed.sh`

## 推荐执行顺序
1. 检查运行时与依赖
2. 检查 MySQL 连接
3. 执行 auth/rbac schema
4. 执行 auth/rbac seed
5. 将 `AUTH_RBAC_SOURCE=real`
6. 验证统一登录与权限接口

## 相关接口验证
- `/api/v1/auth/login`
- `/api/v1/auth/profile`
- `/api/v1/auth/menus`
- `/api/v1/auth/permissions`
