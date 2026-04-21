-- ============================================================
-- 搜题平台 · MySQL 全量初始化脚本
-- 由 database/migrations/*.sql 合并生成，按文件名升序拼接
-- 用法：mysql -u root -p search_platform < init.sql
-- 或宝塔 → 数据库 → search_platform → 导入 → 选 init.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- 0001_auth_rbac_schema.sql
-- ============================================================
-- 0001_auth_rbac_schema.sql

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nickname` varchar(50) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `role_permission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  `permission_code` varchar(100) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0002_search_logs_schema.sql
-- ============================================================
-- 0002_search_logs_schema.sql

CREATE TABLE `search_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_no` varchar(50) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `api_key_id` bigint unsigned DEFAULT NULL,
  `keyword` varchar(255) NOT NULL DEFAULT '',
  `question_type` varchar(30) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `hit_count` int NOT NULL DEFAULT 0,
  `source_type` varchar(30) NOT NULL DEFAULT 'local',
  `consume_quota` int NOT NULL DEFAULT 0,
  `cost_ms` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_log_no` (`log_no`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0003_user_center_schema.sql
-- ============================================================
-- 0003_user_center_schema.sql

CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `frozen_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_recharge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_consume` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_unlimited` tinyint NOT NULL DEFAULT 0,
  `remain_quota` bigint NOT NULL DEFAULT 0,
  `used_quota` bigint NOT NULL DEFAULT 0,
  `expire_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `app_name` varchar(100) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `api_secret_hash` varchar(255) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `expire_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'notice',
  `status` tinyint NOT NULL DEFAULT 1,
  `publish_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0004_docs_schema.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS docs_categories (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS docs_articles (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  summary VARCHAR(500) NULL,
  content_md LONGTEXT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_docs_articles_category_id (category_id)
);


-- ============================================================
-- 0005_api_source_schema.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS api_sources (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(100) NOT NULL,
  method VARCHAR(20) NOT NULL DEFAULT 'GET',
  url VARCHAR(255) NOT NULL,
  timeout INT NOT NULL DEFAULT 10,
  retry_times INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  success_code_field VARCHAR(100) NULL,
  success_code_value VARCHAR(100) NULL,
  data_path VARCHAR(255) NULL,
  remark VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
);


-- ============================================================
-- 0006_collect_schema.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS collect_accounts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  platform VARCHAR(50) NOT NULL,
  account VARCHAR(100) NOT NULL,
  cookie_text LONGTEXT NULL,
  token_text LONGTEXT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  remark VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_collect_accounts_user_id (user_id)
);

CREATE TABLE IF NOT EXISTS collect_tasks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  task_no VARCHAR(100) NOT NULL,
  user_id BIGINT NOT NULL,
  account_id BIGINT NULL,
  collect_type VARCHAR(50) NOT NULL,
  course_count INT NOT NULL DEFAULT 0,
  question_count INT NOT NULL DEFAULT 0,
  success_count INT NOT NULL DEFAULT 0,
  fail_count INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  error_message VARCHAR(500) NULL,
  runner_script VARCHAR(255) NULL,
  next_script VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_collect_tasks_task_no (task_no),
  KEY idx_collect_tasks_user_id (user_id)
);


-- ============================================================
-- 0007_collect_task_extend.sql
-- ============================================================
ALTER TABLE collect_tasks ADD COLUMN account_phone VARCHAR(50) NULL AFTER account_id;
ALTER TABLE collect_tasks ADD COLUMN account_password VARCHAR(100) NULL AFTER account_phone;
ALTER TABLE collect_tasks ADD COLUMN course_ids TEXT NULL AFTER collect_type;


-- ============================================================
-- 0007_plans_system_configs_schema.sql
-- ============================================================
-- 0007_plans_schema.sql

CREATE TABLE IF NOT EXISTS `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `code` varchar(32) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration` int NOT NULL DEFAULT 30 COMMENT 'days',
  `quota` int NOT NULL DEFAULT 0 COMMENT 'search quota, 0=unlimited',
  `is_unlimited` tinyint NOT NULL DEFAULT 0,
  `features` json DEFAULT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `system_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_code` varchar(32) NOT NULL DEFAULT 'general',
  `config_key` varchar(64) NOT NULL,
  `config_value` text NOT NULL,
  `value_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT 'string|number|boolean|json',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_group_code` (`group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0008_question_metadata_schema.sql
-- ============================================================
-- 0008_question_metadata_schema.sql

CREATE TABLE IF NOT EXISTS `question_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned NOT NULL DEFAULT 0,
  `name` varchar(64) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `question_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `question_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `code` varchar(32) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `question_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `sort` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0008_user_api_source_schema.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS user_api_sources (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  name VARCHAR(100) NOT NULL,
  method VARCHAR(20) NOT NULL DEFAULT 'GET',
  url VARCHAR(500) NOT NULL,
  keyword_param VARCHAR(100) NOT NULL DEFAULT 'q',
  keyword_position VARCHAR(20) NOT NULL DEFAULT 'url_param',
  type_param VARCHAR(100) NULL,
  type_position VARCHAR(20) NULL DEFAULT 'url_param',
  option_delimiter VARCHAR(20) NULL DEFAULT '###',
  option_format VARCHAR(255) NULL,
  headers TEXT NULL,
  extra_config TEXT NULL,
  data_path VARCHAR(255) NULL DEFAULT 'data',
  success_code_field VARCHAR(100) NULL DEFAULT 'code',
  success_code_value VARCHAR(100) NULL DEFAULT '1',
  timeout INT NOT NULL DEFAULT 10,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  remark VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_user_status (user_id, status)
);


-- ============================================================
-- 0009_log_tables_schema.sql
-- ============================================================
-- 0009_log_tables_schema.sql

CREATE TABLE IF NOT EXISTS `balance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(30) NOT NULL COMMENT 'recharge|consume|refund',
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_no` varchar(64) NOT NULL DEFAULT '',
  `amount` decimal(10,2) NOT NULL,
  `pay_method` varchar(30) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `operate_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `module` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL DEFAULT '',
  `content` text,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 0010_doc_config_seed.sql
-- ============================================================
INSERT INTO `system_configs` (`group_code`, `config_key`, `config_value`, `value_type`, `status`, `created_at`, `updated_at`)
VALUES ('doc', 'doc_config', '{"api_key":"未配置","multimodal_model":"doubao-seed-1-8-251228","text_model":"doubao-1-5-pro-32k-250115","providers":[{"name":"Doubao Seed 1.8","value":"doubao-seed-1-8-251228","desc":"推荐，支持多模态"},{"name":"Doubao 1.5 Pro 32k","value":"doubao-1-5-pro-32k-250115","desc":"推荐，纯文本，速度快"},{"name":"GLM 4.7","value":"glm-4-7-251222","desc":"智谱模型"}]}', 'json', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW();


-- ============================================================
-- 0011_order_and_payment_config.sql
-- ============================================================
-- 订单表
CREATE TABLE IF NOT EXISTS `order` (
  `id` int unsigned AUTO_INCREMENT PRIMARY KEY,
  `order_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '支付订单号',
  `user_id` int unsigned NOT NULL,
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '1钱包充值 2套餐购买',
  `plan_id` int unsigned DEFAULT NULL COMMENT '套餐ID(type=2时)',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `pay_type` varchar(20) NOT NULL COMMENT 'alipay/wxpay/qqpay/bank',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付 2已过期',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  UNIQUE KEY `uk_order_no` (`order_no`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_trade_no` (`trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单表';

-- 支付配置（system_configs）
INSERT IGNORE INTO `system_configs` (`group_code`, `config_key`, `config_value`, `value_type`, `status`, `created_at`, `updated_at`) VALUES
('payment', 'epay_apiurl', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_pid', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_sign_type', 'MD5', 'string', 1, NOW(), NOW()),
('payment', 'epay_key', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_platform_public_key', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_merchant_private_key', '', 'string', 1, NOW(), NOW());


-- ============================================================
-- 0012_proxies_and_collect_config.sql
-- ============================================================
-- 代理表
CREATE TABLE IF NOT EXISTS `proxies` (
  `id` int unsigned AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL DEFAULT 'default',
  `protocol` varchar(20) NOT NULL COMMENT 'http/https/socks5/socks5h',
  `host` varchar(255) NOT NULL,
  `port` int NOT NULL DEFAULT 8080,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `latency_ms` int DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0未检测 1正常 2异常',
  `used_count` int NOT NULL DEFAULT 0,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理IP表';

-- collect_tasks 加字段
ALTER TABLE `collect_tasks`
  ADD COLUMN `school_name` varchar(100) DEFAULT NULL AFTER `account_password`,
  ADD COLUMN `province` varchar(50) DEFAULT NULL AFTER `school_name`,
  ADD COLUMN `city` varchar(50) DEFAULT NULL AFTER `province`,
  ADD COLUMN `proxy_url` varchar(500) DEFAULT NULL AFTER `city`;

-- 采集配置种子数据
INSERT IGNORE INTO `system_configs` (`group_code`, `config_key`, `config_value`, `value_type`, `status`, `created_at`, `updated_at`) VALUES
('collect', 'collect_concurrency', '1', 'number', 1, NOW(), NOW()),
('collect', 'collect_course_concurrency', '1', 'number', 1, NOW(), NOW()),
('collect', 'collect_request_interval_ms', '120', 'number', 1, NOW(), NOW()),
('collect', 'collect_separator', '###', 'string', 1, NOW(), NOW()),
('collect', 'collect_output_mode', 'json', 'string', 1, NOW(), NOW()),
('collect', 'collect_timeout_seconds', '7200', 'number', 1, NOW(), NOW()),
('collect', 'collect_rate_backoff_ms', '30', 'number', 1, NOW(), NOW()),
('collect', 'collect_rate_recovery_count', '40', 'number', 1, NOW(), NOW()),
('collect', 'collect_login_max_attempts', '5', 'number', 1, NOW(), NOW()),
('collect', 'collect_progress_interval', '10', 'number', 1, NOW(), NOW()),
('collect', 'collect_proxy_cooldown_min', '5', 'number', 1, NOW(), NOW()),
('collect', 'collect_proxy_enabled', '0', 'number', 1, NOW(), NOW());


-- ============================================================
-- 0013_payment_amount_limits.sql
-- ============================================================
INSERT IGNORE INTO system_configs (group_code, config_key, config_value, value_type, status, created_at, updated_at)
VALUES
  ('payment', 'payment_min_amount', '0.01', 'number', 1, NOW(), NOW()),
  ('payment', 'payment_max_amount', '10000', 'number', 1, NOW(), NOW());


-- ============================================================
-- 0014_add_rbac_foreign_keys.sql
-- ============================================================
-- 清理可能存在的孤儿数据
DELETE FROM user_role WHERE user_id NOT IN (SELECT id FROM users);
DELETE FROM user_role WHERE role_id NOT IN (SELECT id FROM roles);
DELETE FROM role_permission WHERE role_id NOT IN (SELECT id FROM roles);
DELETE FROM role_permission WHERE permission_id NOT IN (SELECT id FROM permissions);

ALTER TABLE user_role
  ADD CONSTRAINT fk_user_role_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_user_role_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

ALTER TABLE role_permission
  ADD CONSTRAINT fk_role_perm_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_role_perm_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;


-- ============================================================
-- 0015_doc_slug_unique.sql
-- ============================================================
-- 去重：保留每个 slug 中 id 最小的行，后续行 slug 加后缀
UPDATE docs_articles a
JOIN (
    SELECT slug, MIN(id) AS keep_id
    FROM docs_articles
    GROUP BY slug
    HAVING COUNT(*) > 1
) dup ON a.slug = dup.slug AND a.id != dup.keep_id
SET a.slug = CONCAT(a.slug, '-', a.id);

-- 添加唯一索引
ALTER TABLE docs_articles ADD UNIQUE INDEX uk_slug (slug);


-- ============================================================
-- 0016_order_plan_snapshot.sql
-- ============================================================
-- 订单表增加套餐快照字段
ALTER TABLE `order`
  ADD COLUMN `plan_name` varchar(100) DEFAULT NULL COMMENT '下单时套餐名称快照' AFTER `plan_id`,
  ADD COLUMN `plan_duration` int DEFAULT NULL COMMENT '下单时套餐天数快照' AFTER `plan_name`,
  ADD COLUMN `plan_quota` int DEFAULT NULL COMMENT '下单时套餐额度快照' AFTER `plan_duration`,
  ADD COLUMN `plan_is_unlimited` tinyint DEFAULT NULL COMMENT '下单时是否无限额度快照' AFTER `plan_quota`;


-- ============================================================
-- 0017_users_sessions_invalidated_at.sql
-- ============================================================
-- 为 users 表增加独立的"会话失效时间"列，替代把 updated_at 当作 token 版本号使用的做法。
-- 之前把 updated_at 同时当审计时间戳和 token 版本号，导致用户改昵称/邮箱/头像也会被踢下线。
-- DATETIME(3) 提供毫秒精度，可消除"密码变更 + 登录发生在同一秒"时 updated_at 与 JWT iat 秒级比较的绕过窗口。
ALTER TABLE `users`
    ADD COLUMN `sessions_invalidated_at` DATETIME(3) NULL DEFAULT NULL COMMENT '会话失效时间(毫秒)，此前签发的 token 全部失效';


-- ============================================================
-- 0018_perf_indexes.sql
-- ============================================================
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


SET FOREIGN_KEY_CHECKS = 1;
