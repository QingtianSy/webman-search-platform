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
