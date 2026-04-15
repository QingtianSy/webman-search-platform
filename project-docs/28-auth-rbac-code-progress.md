# Auth / RBAC 真接入代码推进说明

## 本轮新增的代码层改进
1. `UserRepository` 已新增 `findById()`，并具备 mock / real 双模式
2. `RoleRepository::findByIds()` 已具备真实查询分支
3. `AuthService::profile()` 不再硬编码遍历用户名，而是走 Repository
4. `AuthService` 新增 `buildAuthPayload()`，统一 login/profile 组装逻辑
5. `PasswordService` 已作为密码兼容层独立存在

## 这样做的意义
- 后面切真实 users 表时，不再需要在 AuthService 中手工写死用户来源
- profile/login 的 auth payload 组装不会出现两套逻辑
- 未来只要把 users / roles / user_role / role_permission / menus 接通，就能更快验证统一认证主线
