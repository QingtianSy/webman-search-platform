-- 性能优化：补齐高频查询的覆盖索引。
-- 1) search_logs 管理端日志列表按 user_id + 倒序 created_at 分页；旧索引 (user_id, created_at) 走倒序扫描 + 回表取 id。
--    替换为 (user_id, created_at DESC, id)：MySQL 8+ 支持逆序索引；对 7.x 也等价于普通多列索引，不会更差。
--    末尾带 id 让"按 created_at 倒序 + 相同秒内按 id 次排"可走覆盖索引，分页稳定。
-- 2) user_subscriptions 查"用户的未过期订阅"的高频场景 (WHERE user_id=? AND expire_at > NOW())，
--    原索引只有 (user_id)，补 (user_id, expire_at) 让该条件走范围索引而非回表全过滤。
ALTER TABLE `search_logs`
    DROP INDEX `idx_user_created`,
    ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC, `id`);

ALTER TABLE `user_subscriptions`
    ADD INDEX `idx_user_expire` (`user_id`, `expire_at`);
