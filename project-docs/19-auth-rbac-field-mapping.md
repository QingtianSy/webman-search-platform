# Auth / RBAC 字段映射

## 一、users.json → users 表

### mock 字段
- id
- username
- password
- nickname
- avatar
- status
- type

### real 字段
- id
- username
- password_hash
- nickname
- avatar
- status
- created_at
- updated_at

### 说明
- `password` 在真实表中改为 `password_hash`
- `type` 不再保留，管理员身份由角色/权限决定

---

## 二、roles.json → roles 表
- id -> id
- code -> code
- name -> name
- sort -> sort（可后补）
- status -> status（可后补）

---

## 三、permissions.json → permissions 表
- id -> id
- code -> code
- name -> name
- type -> type（当前 mock 未细分时，可统一默认 1）

---

## 四、user_roles.json → user_role 表
- user_id -> user_id
- role_id -> role_id

---

## 五、role_permissions.json → role_permission 表
### 当前 mock
- role_id
- permission_code

### real 表
- role_id
- permission_id

### 说明
需要先把 `permission_code` 映射到 `permissions.id`，再写入 `role_permission`。

---

## 六、menus.json → menus 表
- id -> id
- name -> name
- path -> path
- permission_code -> permission_code
- parent_id -> parent_id（当前 mock 没有时默认 0）
- sort -> sort（可后补）
- status -> status（可后补）

---

## 七、迁移原则
1. 先迁 users / roles / permissions / user_role / role_permission / menus
2. 再让 AuthService 改查真实表
3. 再删除 users.json / roles.json / permissions.json / user_roles.json / role_permissions.json / menus.json 依赖
