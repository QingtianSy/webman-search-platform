ALTER TABLE collect_tasks ADD COLUMN account_phone VARCHAR(50) NULL AFTER account_id;
ALTER TABLE collect_tasks ADD COLUMN account_password VARCHAR(100) NULL AFTER account_phone;
ALTER TABLE collect_tasks ADD COLUMN course_ids TEXT NULL AFTER collect_type;
