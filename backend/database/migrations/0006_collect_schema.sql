CREATE TABLE IF NOT EXISTS collect_accounts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  platform VARCHAR(50) NOT NULL,
  account VARCHAR(100) NOT NULL,
  cookie_text LONGTEXT NULL,
  token_text LONGTEXT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  remark VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  KEY idx_collect_accounts_user_id (user_id)
);

CREATE TABLE IF NOT EXISTS collect_tasks (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  task_no VARCHAR(100) NOT NULL,
  user_id BIGINT NOT NULL,
  account_id BIGINT NULL,
  collect_type VARCHAR(50) NOT NULL,
  course_count INT NOT NULL DEFAULT 0,
  question_count INT NOT NULL DEFAULT 0,
  success_count INT NOT NULL DEFAULT 0,
  fail_count INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  error_message VARCHAR(500) NULL,
  runner_script VARCHAR(255) NULL,
  next_script VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_collect_tasks_task_no (task_no),
  KEY idx_collect_tasks_user_id (user_id)
);
