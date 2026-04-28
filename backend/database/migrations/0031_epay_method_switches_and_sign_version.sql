INSERT IGNORE INTO system_configs (group_code, config_key, config_value, value_type, status, created_at, updated_at)
VALUES
  ('payment', 'epay_alipay_enabled', '1', 'boolean', 1, NOW(), NOW()),
  ('payment', 'epay_wxpay_enabled', '1', 'boolean', 1, NOW(), NOW()),
  ('payment', 'epay_qqpay_enabled', '1', 'boolean', 1, NOW(), NOW());

UPDATE system_configs
SET config_value = CASE UPPER(config_value)
  WHEN 'RSA' THEN 'v2'
  WHEN 'V2' THEN 'v2'
  ELSE 'v1'
END,
updated_at = NOW()
WHERE config_key = 'epay_sign_type'
  AND UPPER(config_value) IN ('MD5', 'RSA', 'V1', 'V2');
