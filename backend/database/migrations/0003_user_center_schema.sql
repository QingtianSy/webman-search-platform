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
