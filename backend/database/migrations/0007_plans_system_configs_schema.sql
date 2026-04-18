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
