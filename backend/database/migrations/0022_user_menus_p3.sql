-- P3 用户端菜单补齐：我的套餐 / 在线充值 / 题库配置 / 通知公告
--
-- 背景：
--   前端 frontend/apps/web-naive/src/views/user/ 下已补齐 4 个业务页，但菜单表没有
--   对应行，accessMode='backend' 下路由不会注册 → /#/user/plan 等直接 404 fallback。
--   本迁移把这 4 条菜单加进 menus 表，前端 normalizeUserPath 会自动前缀 /user。
--
-- 权限：portal.access（所有登录用户可见；后续要按角色限制再加 permission）
-- sort 排在现有用户菜单末尾（12-15），紧随 4 类日志之后。侧栏展示由管理员
--   在 /admin/menu 处拖拽调整，seed 只保证"能看到"即可。
-- 幂等：NOT EXISTS 守护，重复执行不会 duplicate entry。

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '我的套餐', '/plan', 'portal.access', 12, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/plan');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '在线充值', '/recharge', 'portal.access', 13, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/recharge');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '题库配置', '/api-source', 'portal.access', 14, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/api-source');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '通知公告', '/announcement', 'portal.access', 15, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/announcement');
