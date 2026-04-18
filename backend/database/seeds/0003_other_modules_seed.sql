-- 0003_other_modules_seed.sql

-- Plans
INSERT INTO `plans` (`id`, `name`, `code`, `price`, `duration`, `quota`, `is_unlimited`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '免费套餐', 'free',       0.00,   0,  10, 0, 1, 1, NOW(), NOW()),
(2, '月度套餐', 'monthly',   29.90,  30, 1000, 0, 2, 1, NOW(), NOW()),
(3, '年度套餐', 'yearly',   199.00, 365, 0, 1, 3, 1, NOW(), NOW());

-- System configs
INSERT INTO `system_configs` (`id`, `group_code`, `config_key`, `config_value`, `value_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'system', 'site_name',      '爱搜题',  'string',  1, NOW(), NOW()),
(2, 'search', 'default_split',  '###',     'string',  1, NOW(), NOW());

-- Question categories
INSERT INTO `question_categories` (`id`, `parent_id`, `name`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, '计算机基础', 1, 1, NOW(), NOW()),
(2, 0, '数学',       2, 1, NOW(), NOW()),
(3, 0, '英语',       3, 1, NOW(), NOW());

-- Question types
INSERT INTO `question_types` (`id`, `code`, `name`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, 'single', '单选题', 1, 1, NOW(), NOW()),
(2, 'multi',  '多选题', 2, 1, NOW(), NOW()),
(3, 'judge',  '判断题', 3, 1, NOW(), NOW()),
(4, 'fill',   '填空题', 4, 1, NOW(), NOW()),
(5, 'essay',  '简答题', 5, 1, NOW(), NOW());

-- Question sources
INSERT INTO `question_sources` (`id`, `name`, `code`, `url`, `status`, `created_at`, `updated_at`) VALUES
(1, '自建题库', 'local', '', 1, NOW(), NOW());

-- Question tags
INSERT INTO `question_tags` (`id`, `name`, `sort`, `created_at`, `updated_at`) VALUES
(1, '网络', 1, NOW(), NOW()),
(2, '算法', 2, NOW(), NOW()),
(3, '数据库', 3, NOW(), NOW());

-- Docs categories
INSERT INTO `docs_categories` (`id`, `name`, `slug`, `sort`, `status`, `created_at`, `updated_at`) VALUES
(1, '快速开始', 'quickstart', 1, 1, NOW(), NOW()),
(2, 'API文档',  'api',        2, 1, NOW(), NOW());

-- Docs articles
INSERT INTO `docs_articles` (`id`, `category_id`, `title`, `slug`, `summary`, `content_md`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '平台介绍', 'intro', '平台功能概述', '# 欢迎使用爱搜题平台\n\n这是一个高性能题库搜题SaaS平台。', 1, NOW(), NOW());

-- API sources
INSERT INTO `api_sources` (`id`, `name`, `code`, `method`, `url`, `timeout`, `retry_times`, `status`, `created_at`, `updated_at`) VALUES
(1, '本地搜索', 'local', 'POST', 'http://127.0.0.1:8787/api/v1/user/search/query', 10, 1, 1, NOW(), NOW());
