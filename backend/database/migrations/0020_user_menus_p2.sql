-- P2 用户端菜单补齐：文档中心、采集任务、四类日志。
-- 背景：accessMode='backend'，路由完全由 /auth/menus 下发；P2 新增页面需在 menus 表登记
-- 对应后端路径后，前端 normalizeUserPath 会自动前缀 /user。
--
-- 权限口径：
--   /doc /log/* 走 portal.access（所有登录用户可见）
--   /collect 走 portal.access（采集是用户自助功能，不单独建 collect.query 权限）
-- 如果以后要按用户级别区分采集权限，再加 collect.query permission + role_permission 即可。
--
-- sort 规划：
--   1 首页 / 2 搜题 / 3 搜题日志 / 4 钱包 / 5 API Key / 6 文档 / 7 采集
--   余额/支付/登录/操作 日志放 8-11，聚合在侧栏末尾。
--
-- 幂等：NOT EXISTS 守护，重复执行不会 duplicate entry。
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '文档中心', '/doc', 'portal.access', 6, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/doc');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '采集任务', '/collect', 'portal.access', 7, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/collect');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '余额流水', '/log/balance', 'portal.access', 8, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/log/balance');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '支付记录', '/log/payment', 'portal.access', 9, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/log/payment');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '登录记录', '/log/login', 'portal.access', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/log/login');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '操作记录', '/log/operate', 'portal.access', 11, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/log/operate');
