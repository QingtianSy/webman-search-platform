-- 0032_drop_question_dict_menus.sql
--
-- 删除 0025 插入的 4 条字典子页菜单（题目分类/题型字典/题库来源/题目标签）。
-- 这些字典页已从代码中移除，菜单条目也需清理。

DELETE FROM `menus` WHERE `path` IN (
    '/admin/question-category',
    '/admin/question-type',
    '/admin/question-source',
    '/admin/question-tag'
);
