-- 为 users 表增加独立的"会话失效时间"列，替代把 updated_at 当作 token 版本号使用的做法。
-- 之前把 updated_at 同时当审计时间戳和 token 版本号，导致用户改昵称/邮箱/头像也会被踢下线。
-- DATETIME(3) 提供毫秒精度，可消除"密码变更 + 登录发生在同一秒"时 updated_at 与 JWT iat 秒级比较的绕过窗口。
ALTER TABLE `users`
    ADD COLUMN `sessions_invalidated_at` DATETIME(3) NULL DEFAULT NULL COMMENT '会话失效时间(毫秒)，此前签发的 token 全部失效';
