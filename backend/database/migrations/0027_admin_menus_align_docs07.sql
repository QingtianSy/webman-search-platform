-- 0027_admin_menus_align_docs07.sql
--
-- 管理端侧边栏对齐 docs/07 第一节：
--   1. 新建第 5 个分组「系统工具」(/admin/group/tools)
--   2. /admin/plan 从「用户管理」迁到「系统管理」(docs §1.系统管理 sort 8)
--   3. /admin/proxy /admin/collect /admin/collect-config 迁到「系统工具」组
--   4. /admin/payment-config 权限码 admin.access → system.config（docs 标注口径）
--
-- 0021 当时未建 tools 组（"待有第一个工具时再加"的注释），现在 proxy / collect /
-- collect-config 三个工具页齐了，归位。
--
-- 幂等：父组 NOT EXISTS 守 path 唯一；子项 UPDATE parent_id 用子查询动态拿 id，
-- 重复执行结果一致。

-- 1. 新建「系统工具」分组（sort=24，接续 0021 的 20-23）
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '系统工具', '/admin/group/tools', 'admin.access', 24, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/group/tools');

-- 2. /admin/plan 从 users 组挪到 system 组（docs/07 §1.系统管理 sort 8）
UPDATE `menus`
SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t),
    `sort` = 9
WHERE `path` = '/admin/plan';

-- 3. 三个工具页迁入 tools 组
UPDATE `menus`
SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/tools') AS t),
    `sort` = 1
WHERE `path` = '/admin/proxy';

UPDATE `menus`
SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/tools') AS t),
    `sort` = 2
WHERE `path` = '/admin/collect';

UPDATE `menus`
SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/tools') AS t),
    `sort` = 3
WHERE `path` = '/admin/collect-config';

-- 4. /admin/payment-config 权限码对齐 docs（system.config）
UPDATE `menus`
SET `permission_code` = 'system.config'
WHERE `path` = '/admin/payment-config';

-- 5. 监控组重排 sort：proxy 已迁出，重排 log×4 + monitor 让顺序紧凑
UPDATE `menus` SET `sort` = 1 WHERE `path` = '/admin/log/search';
UPDATE `menus` SET `sort` = 2 WHERE `path` = '/admin/log/balance';
UPDATE `menus` SET `sort` = 3 WHERE `path` = '/admin/log/payment';
UPDATE `menus` SET `sort` = 4 WHERE `path` = '/admin/log/operate';
UPDATE `menus` SET `sort` = 5 WHERE `path` = '/admin/log/login';
UPDATE `menus` SET `sort` = 6 WHERE `path` = '/admin/monitor';
