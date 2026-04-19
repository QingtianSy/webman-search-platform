-- 清理可能存在的孤儿数据
DELETE FROM user_role WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM user_role WHERE role_id NOT IN (SELECT id FROM roles);
DELETE FROM role_permission WHERE role_id NOT IN (SELECT id FROM roles);
DELETE FROM role_permission WHERE permission_id NOT IN (SELECT id FROM permissions);

ALTER TABLE user_role
  ADD CONSTRAINT fk_user_role_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_user_role_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

ALTER TABLE role_permission
  ADD CONSTRAINT fk_role_perm_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_role_perm_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;
