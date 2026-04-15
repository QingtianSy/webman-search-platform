# 数据库设计

## 一、MySQL

### 核心表
- users
- admins
- roles
- permissions
- menus
- plans
- user_subscriptions
- wallets
- wallet_logs
- orders
- quota_logs
- user_api_keys
- user_ip_whitelists
- user_settings
- user_model_configs
- question_categories
- question_types
- question_sources
- question_tags
- search_logs
- collect_accounts
- collect_tasks
- collect_task_courses
- collect_task_errors
- api_sources
- api_source_params
- api_source_test_logs
- docs_categories
- docs_articles
- announcements
- system_configs

### 设计原则
- 强关系与强事务数据放 MySQL
- 所有核心表保留 created_at / updated_at
- 所有关键业务表保留 status
- 敏感信息不明文存储

## 二、MongoDB

### 核心集合
- questions
- collect_raw_results
- search_log_details
- api_debug_logs
- ai_answer_records（预留）

### questions 结构要点
- question_id
- md5
- category_id/category_name
- type_id/type_code/type_name
- source_id/source_name
- stem
- stem_plain
- options
- options_text
- answers
- answer_text
- analysis
- difficulty
- tags
- status
- created_at
- updated_at

## 三、Elasticsearch

### 索引
- question_index
- search_log_index
- collect_log_index
- api_request_log_index（预留）

### question_index 字段
- question_id
- md5
- stem
- stem_plain
- options_text
- answer_text
- analysis
- type_code
- type_name
- source_id
- source_name
- category_id
- category_name
- tags
- status
- created_at

## 四、Redis

### Key 设计
- token:user:{user_id}
- token:admin:{admin_id}
- perm:admin:{admin_id}
- menu:admin:{admin_id}
- api_key:{api_key}
- quota:user:{user_id}
- quota:key:{api_key_id}
- rate:user:{user_id}:{api}
- rate:ip:{ip}:{api}
- rate:key:{api_key}:{api}
- search:result:{md5(query)}
- hot:search
- collect:task:{task_id}
- lock:quota:{user_id}
- lock:collect:{task_id}
- lock:index:question:{question_id}

## 五、落地顺序

### 第一批
- users
- admins
- roles
- permissions
- menus
- user_api_keys
- plans
- user_subscriptions
- wallets
- orders
- search_logs
- question_categories
- question_types
- question_sources
- Mongo questions
- Mongo search_log_details
- ES question_index

### 第二批
- collect_accounts
- collect_tasks
- collect_task_courses
- api_sources
- api_source_params
- docs_categories
- docs_articles
- announcements
- system_configs
- Mongo collect_raw_results
- Mongo api_debug_logs
- ES search_log_index
- ES collect_log_index
