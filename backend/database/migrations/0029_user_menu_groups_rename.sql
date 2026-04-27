-- 0029_user_menu_groups_rename.sql
--
-- 微调用户端分组：
--   1. /dashboard 从 mine 组挪回平铺（parent_id=0），保持侧栏首位
--   2. 「我的」分组改名为「系统导航」
--
-- 背景：0028 把 /dashboard 也挂进了 mine 组（docs/07 §1.L14 这么写的），但实际
-- 体验上首页应当独立于分组、点击直达，不需要展开任何组。改名也是用户偏好。
--
-- 幂等：UPDATE 重复执行结果一致。

-- 1. /dashboard 回到平铺（sort=1 占首位）
UPDATE `menus` SET `parent_id` = 0, `sort` = 1 WHERE `path` = '/dashboard';

-- 2. 「我的」改名「系统导航」
UPDATE `menus` SET `name` = '系统导航' WHERE `path` = '/group/mine';

-- 3. mine 组内剩余子项重排 sort（首页移走后，搜题成第 1）
UPDATE `menus` SET `sort` = 1 WHERE `path` = '/search';
UPDATE `menus` SET `sort` = 2 WHERE `path` = '/wallet';
UPDATE `menus` SET `sort` = 3 WHERE `path` = '/api-key';
UPDATE `menus` SET `sort` = 4 WHERE `path` = '/plan';
UPDATE `menus` SET `sort` = 5 WHERE `path` = '/recharge';
UPDATE `menus` SET `sort` = 6 WHERE `path` = '/api-source';
UPDATE `menus` SET `sort` = 7 WHERE `path` = '/announcement';
UPDATE `menus` SET `sort` = 8 WHERE `path` = '/doc';
UPDATE `menus` SET `sort` = 9 WHERE `path` = '/collect';
