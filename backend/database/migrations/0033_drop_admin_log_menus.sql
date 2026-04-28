-- 0033_drop_admin_log_menus.sql
--
-- 删除管理端侧边栏的 5 个日志菜单入口（搜索日志/余额日志/支付日志/操作日志/登录日志）。
-- 页面和 API 代码保留，仅移除菜单。

DELETE FROM `menus` WHERE `path` IN (
    '/admin/log/search',
    '/admin/log/balance',
    '/admin/log/payment',
    '/admin/log/operate',
    '/admin/log/login'
);
