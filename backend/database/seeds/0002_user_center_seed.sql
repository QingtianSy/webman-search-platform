-- 0002_user_center_seed.sql

INSERT INTO wallets (user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at)
VALUES (1, 0.00, 0.00, 0.00, 0.00, NOW(), NOW());

INSERT INTO user_subscriptions (user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at)
VALUES (1, '无套餐', 0, 1000, 0, NULL, NOW(), NOW());

-- 注意：api_secret_hash 必须是 password_hash('sk_demo_001', PASSWORD_BCRYPT) 的结果，
-- 不能直接写明文 'sk_demo_001'——ApiKeyService::verify 只认 password_verify()，明文会 100% 校验失败。
INSERT INTO user_api_keys (user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at)
VALUES (1, '默认测试应用', 'ak_demo_001', '$2y$10$9L3gPj/VK72b0S3XNuYogeZIKt3DjKCTLB9yt6zGVbjIGF/uOpr8u', 1, NULL, NOW(), NOW());

INSERT INTO announcements (title, content, type, status, publish_at, created_at, updated_at)
VALUES ('欢迎使用平台', '欢迎使用搜题平台，如有问题请联系管理员。', 'notice', 1, NOW(), NOW(), NOW());
