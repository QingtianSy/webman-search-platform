-- 订单表增加套餐快照字段
ALTER TABLE `order`
  ADD COLUMN `plan_name` varchar(100) DEFAULT NULL COMMENT '下单时套餐名称快照' AFTER `plan_id`,
  ADD COLUMN `plan_duration` int DEFAULT NULL COMMENT '下单时套餐天数快照' AFTER `plan_name`,
  ADD COLUMN `plan_quota` int DEFAULT NULL COMMENT '下单时套餐额度快照' AFTER `plan_duration`,
  ADD COLUMN `plan_is_unlimited` tinyint DEFAULT NULL COMMENT '下单时是否无限额度快照' AFTER `plan_quota`;
