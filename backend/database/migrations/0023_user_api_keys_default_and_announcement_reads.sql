-- Phase 1 · 0023：给 api-key 加默认位 + 新建公告已读表
--
-- 需求背景：
--   user/api-key/set-default 要求"同用户仅一条 is_default=1"。原表 0003 没有此列，前端页面点"设为默认"
--   目前走 try/catch 静默兜底。本迁移补齐列 + 唯一索引（软索引用 WHERE is_default=1 的部分唯一模拟）。
--   announcement_reads 用于 /user/announcement/read 记录已读；前端红点角标依赖它。
--   两段都用 IF NOT EXISTS / 检查重复守护，幂等可重跑。
--

-- A. user_api_keys.is_default
-- MySQL 5.7+ 不支持 ADD COLUMN IF NOT EXISTS；用 information_schema 先判断。
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_api_keys'
    AND COLUMN_NAME = 'is_default'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `user_api_keys` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1=\u9ed8\u8ba4\u5bc6\u94a5\uff0c\u540c\u7528\u6237\u4ec5\u4e00\u6761'' AFTER `status`',
  'SELECT 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 普通索引（不是唯一，避免历史数据清理前阻塞；默认 key 唯一性由 service 层 transaction 保证）
SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_api_keys'
    AND INDEX_NAME = 'idx_user_default'
);
SET @ddl := IF(@idx_exists = 0,
  'ALTER TABLE `user_api_keys` ADD INDEX `idx_user_default` (`user_id`, `is_default`)',
  'SELECT 1'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- B. announcement_reads
CREATE TABLE IF NOT EXISTS `announcement_reads` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `announcement_id` BIGINT UNSIGNED NOT NULL,
  `read_at` DATETIME NOT NULL,
  PRIMARY KEY (`user_id`, `announcement_id`),
  KEY `idx_announcement` (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告已读记录';
