INSERT IGNORE INTO system_configs (group_code, config_key, config_value, value_type, status, created_at, updated_at)
VALUES
  ('payment', 'payment_min_amount', '0.01', 'number', 1, NOW(), NOW()),
  ('payment', 'payment_max_amount', '10000', 'number', 1, NOW(), NOW());
