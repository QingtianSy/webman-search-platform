-- 0001_auth_rbac_seed.sql

INSERT INTO `roles` (`id`, `name`, `code`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '普通用户', 'user', 1, 1, NOW(), NOW()),
(2, '管理员', 'admin', 2, 1, NOW(), NOW());

INSERT INTO `permissions` (`id`, `name`, `code`, `type`, `created_at`, `updated_at`) VALUES
(1, '用户端访问', 'portal.access', 1, NOW(), NOW()),
(2, '搜题', 'search.query', 1, NOW(), NOW()),
(3, '管理端访问', 'admin.access', 1, NOW(), NOW()),
(4, '题目管理', 'question.manage', 1, NOW(), NOW()),
(5, '系统配置', 'system.config', 1, NOW(), NOW());

INSERT INTO `menus` (`id`, `parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, '首页', '/dashboard', 'portal.access', 1, 1, NOW(), NOW()),
(2, 0, '搜题日志', '/logs/search', 'search.query', 2, 1, NOW(), NOW()),
(3, 0, '题目管理', '/admin/question', 'question.manage', 3, 1, NOW(), NOW()),
(4, 0, '系统配置', '/admin/system-config', 'system.config', 4, 1, NOW(), NOW());

INSERT INTO `users` (`id`, `username`, `password_hash`, `nickname`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(1, 'demo_user', 'PLACEHOLDER_HASH', '测试用户', '', 1, NOW(), NOW()),
(2, 'admin', 'PLACEHOLDER_HASH', '超级管理员', '', 1, NOW(), NOW());

INSERT INTO `user_role` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 2);

INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5);
