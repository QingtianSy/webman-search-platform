# Auth / RBAC Repository 切换方案

## 一、目标
在不修改 Controller / Service 调用方式的前提下，让认证权限相关 Repository 支持：
- mock 模式
- real 模式

## 二、切换方式
通过：
- `config/integration.php`
- `AUTH_RBAC_SOURCE=mock|real`

控制 Repository 内部实际数据来源。

## 三、当前策略
### 对外方法保持不变
例如：
- `findByUsername()`
- `findByIds()`
- `roleIdsByUserId()`
- `permissionCodesByRoleIds()`
- `all()`

### 对内拆分为
- `findByUsernameMock()`
- `findByUsernameReal()`

## 四、好处
- AuthService 不需要因为底层切换而大改
- 前端接口不需要跟着改
- 可以逐个 Repository 替换为真实实现

## 五、后续真实实现建议
### UserRepository
- 查 `users`

### RoleRepository
- 查 `roles`

### PermissionRepository
- 查 `permissions`

### UserRoleRepository
- 查 `user_role`

### RolePermissionRepository
- 联表 `role_permission + permissions`

### MenuRepository
- 查 `menus`
