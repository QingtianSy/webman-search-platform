# Auth / RBAC 代码替换准备清单

## 一、已完成的准备
- `PasswordService` 已抽出，未来可直接切换到 `password_hash` 校验
- `AuthService` 不再直接比较明文密码，改由密码服务校验
- `UserRepository`、`RoleRepository`、`PermissionRepository`、`UserRoleRepository`、`RolePermissionRepository`、`MenuRepository` 已补齐 real-ready 注释

## 二、下一步替换建议
### Step 1
先让 `UserRepository` 支持真实 users 表查询

### Step 2
让 `RoleRepository` / `PermissionRepository` / `UserRoleRepository` / `RolePermissionRepository` / `MenuRepository` 改查真实表

### Step 3
保持 `AuthService` 方法签名不变，只替换内部数据来源

## 三、重点原则
- 控制器不改路径
- Service 不改方法名
- 前端不改字段结构
- token payload 结构尽量保持一致
