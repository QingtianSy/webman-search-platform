-- 0030_collect_tasks_courses_snapshot.sql
--
-- 采集任务存课程快照：提交时把所选课程的 {courseId, courseName} 序列化进 collect_tasks，
-- 详情页"查看课程"列表才能拿到课程名。否则只剩 course_ids 字符串，前端无法显示名称。
--
-- 幂等：IF NOT EXISTS 的列添加；MySQL 8 支持 ALTER TABLE ADD COLUMN IF NOT EXISTS。
-- 若部署环境 MySQL 版本不支持，迁移脚本应忽略 1060 重复列错误（迁移 runner 已处理）。

ALTER TABLE `collect_tasks` ADD COLUMN `courses_snapshot` TEXT NULL COMMENT '提交时课程快照 JSON：[{courseId, courseName}]';
