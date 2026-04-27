-- 0028_user_menu_groups.sql
--
-- 用户端侧边栏分组（docs/07 第一节）：
--   · 我的（/group/mine）：首页 / 搜题 / 钱包 / API Key / 套餐 / 充值 / 题库 / 公告 / 文档 / 采集
--   · 日志（/group/logs）：搜题 / 余额 / 支付 / 登录 / 操作
--
-- 0019-0022 把所有用户菜单都放在 parent_id=0（扁平），侧边栏一长条 13 项。
-- docs/07 要求两层分组。本迁移新建 2 个分组 + 重挂 13 个叶子 + 重置 sort。
--
-- 命名：分组 path 用裸的 /group/{mine,logs}（前端 normalizeUserPath 会自动前缀 /user
-- 变成 /group/mine /group/logs，跟子项 /user/xxx 在同一命名空间下）；
-- 前端 menu.ts 看到 children 会渲染为 NSubMenu。
--
-- 权限：两个分组都走 portal.access（全员可见）；子项权限保持原样。
--
-- sort 规划：
--   mine  组 sort=10（排在 admin 的 19+ 组之前）
--   logs  组 sort=11
--   子项在组内重新排序 1-N，docs/07 表格顺序
--
-- 幂等：父组 NOT EXISTS 守 path；子项 UPDATE parent_id 重复执行结果一致。

-- 1. 新建两个分组
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '我的', '/group/mine', 'portal.access', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/group/mine');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '日志', '/group/logs', 'portal.access', 11, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/group/logs');

-- 2. 「我的」组：10 个子项（docs/07 顺序：首页 2 / 搜题 3 / 钱包 4 / API Key 5 /
--    套餐 6 / 充值 7 / 题库 8 / 公告 9 / 文档 10 / 采集 11）
--    注：首页 /dashboard 按 docs/07 L14 挂 mine 组首位；原 0001 seed 为 parent=0 sort=1
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 1 WHERE `path` = '/dashboard';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 2 WHERE `path` = '/search';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 3 WHERE `path` = '/wallet';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 4 WHERE `path` = '/api-key';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 5 WHERE `path` = '/plan';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 6 WHERE `path` = '/recharge';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 7 WHERE `path` = '/api-source';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 8 WHERE `path` = '/announcement';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 9 WHERE `path` = '/doc';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/mine') AS t), `sort` = 10 WHERE `path` = '/collect';

-- 3. 「日志」组：5 个子项（搜题 / 余额 / 支付 / 登录 / 操作）
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/logs') AS t), `sort` = 1 WHERE `path` = '/log/search';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/logs') AS t), `sort` = 2 WHERE `path` = '/log/balance';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/logs') AS t), `sort` = 3 WHERE `path` = '/log/payment';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/logs') AS t), `sort` = 4 WHERE `path` = '/log/login';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/group/logs') AS t), `sort` = 5 WHERE `path` = '/log/operate';
