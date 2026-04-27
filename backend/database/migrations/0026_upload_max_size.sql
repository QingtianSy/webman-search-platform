-- 通用上传最大尺寸（字节）— 给管理端 /admin/upload 用
-- 默认 5MB；运维改 system_configs 即可生效，无需重启
INSERT INTO `system_configs` (`group_code`, `config_key`, `config_value`, `value_type`, `status`, `created_at`, `updated_at`)
VALUES ('upload', 'upload_max_size', '5242880', 'int', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW();
