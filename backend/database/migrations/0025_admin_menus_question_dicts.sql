-- 0025_admin_menus_question_dicts.sql
--
-- P3 补 4 条字典子页菜单（接续 0021 /admin/question 所属的 /admin/group/questions 组）:
--   · 题目分类 /admin/question-category
--   · 题型字典 /admin/question-type
--   · 题库来源 /admin/question-source
--   · 题目标签 /admin/question-tag
--
-- 背景：
--   后端 0008 已有 question_categories/types/sources/tags 四张表 + controller + route (0021
--   之前就存在)，但前端 views/admin/ 没有字典 CRUD 页，侧栏也没有入口。Phase 2 P3 补齐。
--
-- 权限：admin.access（operator 默认不可见，参考 /admin/question 自身）
--
-- 幂等：NOT EXISTS 守 path 唯一；parent_id 子查询动态取 /admin/group/questions。
-- 排序：接续 0021 的 sort 1(/admin/question)，继续 2-5。

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/questions') AS t),
       '题目分类', '/admin/question-category', 'admin.access', 2, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/question-category');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/questions') AS t),
       '题型字典', '/admin/question-type', 'admin.access', 3, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/question-type');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/questions') AS t),
       '题库来源', '/admin/question-source', 'admin.access', 4, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/question-source');

INSERT INTO `menus` (`parent_id`, `name`, `path`, `permission_code`, `sort`, `status`, `created_at`, `updated_at`)
SELECT (SELECT id FROM (SELECT id FROM `menus` WHERE `path` = '/admin/group/questions') AS t),
       '题目标签', '/admin/question-tag', 'admin.access', 5, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `path` = '/admin/question-tag');
