-- 补齐用户端缺失菜单：钱包、API Key。
-- 背景：前端 accessMode='backend'，路由树完全由 GET /auth/menus 下发；
--   前端 menu.ts normalizeUserPath 约定 /api-key → /user/api-key、/wallet → /user/wallet。
-- 原 0001_auth_rbac_seed 只放了 /dashboard /search /log/search 三条用户菜单，
-- 缺这两条 → /#/user/api-key /#/user/wallet 根本没注册路由，直接 404。
-- 权限沿用 portal.access（普通用户 role 已持有），不引入新 permission。
--
-- 幂等：两条路径都可能已经被手动补过；用 NOT EXISTS 守护，重复执行不会报 Duplicate entry。
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '钱包', '/wallet', 'portal.access', 4, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/wallet');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, 'API Key', '/api-key', 'portal.access', 5, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/api-key');
