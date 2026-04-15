# Auth / RBAC 真替换方案

## 一、目标
把当前基于 mock JSON 的统一认证与权限体系，替换为真实数据库实现，且尽量不改控制器接口。

## 二、替换范围
### 当前主要类
- `AuthService`
- `UserRepository`
- `RoleRepository`
- `PermissionRepository`
- `UserRoleRepository`
- `RolePermissionRepository`
- `MenuRepository`

### 目标存储
- MySQL：`users`、`roles`、`permissions`、`user_role`、`role_permission`、`menus`
- Redis：token / auth cache（后续接入）

## 三、替换顺序
### Step 1
先落库表结构：
- users
- roles
- permissions
- user_role
- role_permission
- menus

### Step 2
先替换 Repository：
- 保持方法名不变
- 保持控制器调用不变
- 先让 AuthService 不感知底层存储变化

### Step 3
替换 AuthService：
- 登录校验改查真实 users
- 角色/权限/菜单改查真实表
- default_portal 仍按 `admin.access` 判断

### Step 4
再接 token / cache：
- token 可先维持当前占位风格
- 后续再接 Redis 与真实 JWT

## 四、尽量保持不变的部分
- 接口路径
- 返回结构
- roles / permissions / menus 字段名
- 前端 store 结构

## 五、替换时注意事项
1. 不要再依赖 users.json 中的临时字段
2. `admin` 身份由权限决定，不由独立表决定
3. `menus` 由 permission_code 过滤
4. 认证失败和权限失败错误码保持稳定

## 六、替换完成后的验证顺序
1. `/api/v1/auth/login`
2. `/api/v1/auth/profile`
3. `/api/v1/auth/menus`
4. `/api/v1/auth/permissions`
5. `/api/v1/admin/*` 权限校验
