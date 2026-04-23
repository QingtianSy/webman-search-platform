-- 0021_admin_menu_groups.sql
--
-- 管理端侧边栏分组（六组）：首页 / 用户管理 / 题目管理 / 系统管理 / 系统监控 / 系统工具
-- 方案 B：按"什么模块"分类（不是按"对谁"）
--
-- 背景：0001 seed 把所有菜单都放在 parent_id=0（平铺），用户登录后管理员侧边栏
-- 一长条 13 项无分组。本迁移把其中 12 项收纳到 4 个分组下（首页保持平铺、
-- 系统工具暂不建，待有第一个工具时再加）。
--
-- 幂等保证：
--   - 父组 INSERT 用 NOT EXISTS 守 path 唯一
--   - 子项 parent_id 用 UPDATE 覆盖（重复执行把同一目标再写一次）
--   - 不使用固定 id；父组 id 由 AUTO_INCREMENT 分配，子项 parent_id 通过
--     子查询 SELECT id FROM menus WHERE path=... 动态定位

-- 1. 新建 4 个父组（均挂 admin.access 权限：admin/super_admin 都能看到整个组；
--    operator 角色只有 portal.access + search.query + question.manage + log.view
--    所以在 operator 视角下这些组会整体隐藏，符合预期）
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '用户管理', '/admin/group/users', 'admin.access', 20, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/group/users');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '题目管理', '/admin/group/questions', 'admin.access', 21, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/group/questions');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '系统管理', '/admin/group/system', 'admin.access', 22, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/group/system');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT 0, '系统监控', '/admin/group/monitor', 'admin.access', 23, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/group/monitor');

-- 2. 管理首页保持平铺（parent_id=0，sort=19 排在所有组之前但在用户端菜单之后）
UPDATE `menus` SET `parent_id` = 0, `sort` = 19 WHERE `path` = '/admin/dashboard';

-- 3. 子项重新挂父 + 重置组内 sort
-- 用户管理组：用户/角色/权限/套餐
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/users') AS t), `sort` = 1, `name` = '用户列表' WHERE `path` = '/admin/user';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/users') AS t), `sort` = 2 WHERE `path` = '/admin/role';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/users') AS t), `sort` = 3 WHERE `path` = '/admin/permission';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/users') AS t), `sort` = 4 WHERE `path` = '/admin/plan';

-- 题目管理组：题库
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/questions') AS t), `sort` = 1, `name` = '题库列表' WHERE `path` = '/admin/question';

-- 系统管理组：菜单/系统配置/公告/文档/API源
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t), `sort` = 1 WHERE `path` = '/admin/menu';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t), `sort` = 2 WHERE `path` = '/admin/system-config';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t), `sort` = 3 WHERE `path` = '/admin/announcement';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t), `sort` = 4 WHERE `path` = '/admin/doc';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t), `sort` = 5 WHERE `path` = '/admin/api-source';

-- 系统监控组：搜索日志/采集管理
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t), `sort` = 1 WHERE `path` = '/admin/log/search';
UPDATE `menus` SET `parent_id` = (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t), `sort` = 2 WHERE `path` = '/admin/collect';
