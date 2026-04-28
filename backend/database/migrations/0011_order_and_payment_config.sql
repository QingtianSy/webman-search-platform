-- 订单表
CREATE TABLE IF NOT EXISTS `order` (
  `id` int unsigned AUTO_INCREMENT PRIMARY KEY,
  `order_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '支付订单号',
  `user_id` int unsigned NOT NULL,
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '1钱包充值 2套餐购买',
  `plan_id` int unsigned DEFAULT NULL COMMENT '套餐ID(type=2时)',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `pay_type` varchar(20) NOT NULL COMMENT 'alipay/wxpay/qqpay',
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
('payment', 'epay_alipay_enabled', '1', 'boolean', 1, NOW(), NOW()),
('payment', 'epay_wxpay_enabled', '1', 'boolean', 1, NOW(), NOW()),
('payment', 'epay_qqpay_enabled', '1', 'boolean', 1, NOW(), NOW()),
('payment', 'epay_pid', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_sign_type', 'v1', 'string', 1, NOW(), NOW()),
('payment', 'epay_key', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_platform_public_key', '', 'string', 1, NOW(), NOW()),
('payment', 'epay_merchant_private_key', '', 'string', 1, NOW(), NOW());
