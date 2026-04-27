-- 0024_admin_menus_phase2.sql
--
-- Phase 2 管理端新页菜单入库（10 条）：
--   · 系统监控组：4 类日志页 + 监控面板 + 代理池
--   · 系统管理组：3 类配置页（支付 / 文档全局 / 采集）
--
-- 背景：
--   Phase 2 前端 views/admin/ 下新增了 9 个新目录（log/{balance,payment,operate,login}
--   + proxy + collect-config + payment-config + doc-config + monitor），但菜单表没有
--   对应行，accessMode='backend' 下路由不会注册 → 访问 /#/admin/proxy 等会走
--   PLACEHOLDER_COMPONENT 兜底，侧栏也不显示。
--
-- 分组归属（沿用 0021 的 4 组方案）：
--   · 系统管理组（/admin/group/system）：payment-config / doc-config / collect-config
--   · 系统监控组（/admin/group/monitor）：log/balance / log/payment / log/operate /
--                                          log/login / proxy / monitor
--
-- 权限：admin.access（admin/super_admin 可见；operator 整组隐藏）
--
-- 幂等：NOT EXISTS 守 path 唯一；parent_id 用子查询动态定位，重复执行不会 duplicate。

-- ========== 系统监控组 ==========

-- 4 类新日志页（接续 0021 中 search/collect 的 sort 1,2）
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '余额日志', '/admin/log/balance', 'admin.access', 3, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/log/balance');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '支付日志', '/admin/log/payment', 'admin.access', 4, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/log/payment');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '操作日志', '/admin/log/operate', 'admin.access', 5, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/log/operate');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '登录日志', '/admin/log/login', 'admin.access', 6, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/log/login');

-- 代理池 + 系统监控面板
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '代理池', '/admin/proxy', 'admin.access', 7, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/proxy');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/monitor') AS t),
       '系统监控', '/admin/monitor', 'admin.access', 8, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/monitor');

-- ========== 系统管理组 ==========

-- 接续 0021 中的 sort 5（/admin/api-source）
INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t),
       '支付配置', '/admin/payment-config', 'admin.access', 6, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/payment-config');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t),
       '文档全局配置', '/admin/doc-config', 'admin.access', 7, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/doc-config');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/system') AS t),
       '采集配置', '/admin/collect-config', 'admin.access', 8, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/collect-config');
