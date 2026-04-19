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
