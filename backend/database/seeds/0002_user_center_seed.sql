-- 0002_user_center_seed.sql

INSERT INTO wallets (user_id, balance, frozen_balance, total_recharge, total_consume, created_at, updated_at)
VALUES (1, 0.00, 0.00, 0.00, 0.00, NOW(), NOW());

INSERT INTO user_subscriptions (user_id, name, is_unlimited, remain_quota, used_quota, expire_at, created_at, updated_at)
VALUES (1, '无套餐', 0, 1000, 0, NULL, NOW(), NOW());

INSERT INTO user_api_keys (user_id, app_name, api_key, api_secret_hash, status, expire_at, created_at, updated_at)
VALUES (1, '默认测试应用', 'ak_demo_001', 'sk_demo_001', 1, NULL, NOW(), NOW());

INSERT INTO announcements (title, content, type, status, publish_at, created_at, updated_at)
VALUES ('欢迎使用平台', '当前为项目骨架阶段，后续将逐步接入真实业务能力。', 'notice', 1, NOW(), NOW(), NOW());
