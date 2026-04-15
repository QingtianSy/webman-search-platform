# Auth / RBAC Real 分支伪查询说明

## 一、UserRepository::findByUsernameReal
```sql
SELECT id, username, password_hash, nickname, avatar, mobile, email, status,
       last_login_ip, last_login_at, created_at, updated_at
FROM users
WHERE username = :username
LIMIT 1;
```

## 二、RoleRepository::allReal
```sql
SELECT id, name, code, sort, status, created_at, updated_at
FROM roles
WHERE status = 1;
```

## 三、PermissionRepository::allReal
```sql
SELECT id, name, code, type, created_at, updated_at
FROM permissions;
```

## 四、UserRoleRepository::roleIdsByUserIdReal
```sql
SELECT role_id
FROM user_role
WHERE user_id = :user_id;
```

## 五、RolePermissionRepository::permissionCodesByRoleIdsReal
```sql
SELECT p.code
FROM role_permission rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE rp.role_id IN (:role_ids);
```

## 六、MenuRepository::allReal
```sql
SELECT id, parent_id, name, path, permission_code, sort, status
FROM menus
WHERE status = 1
ORDER BY sort ASC, id ASC;
```

## 七、替换原则
- 先保证字段结构与 mock 返回兼容
- 先不优化缓存
- 先跑通 login / profile / menus / permissions
- Redis token 与 auth cache 在后续批次补齐
