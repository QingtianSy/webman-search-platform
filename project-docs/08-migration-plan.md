# Migration 规划

## 一、MySQL 表优先级

### P0：认证与权限
- users
- roles
- permissions
- user_role
- role_permission
- menus

### P1：用户中心
- wallets
- user_subscriptions
- user_api_keys
- announcements

### P2：题库基础字典
- question_categories
- question_types
- question_sources
- question_tags

### P3：日志与配置
- search_logs
- system_configs
- docs_categories
- docs_articles
- api_sources

### P4：采集与扩展
- collect_accounts
- collect_tasks
- collect_task_courses
- collect_task_errors

## 二、MongoDB 集合优先级
- questions
- search_log_details
- collect_raw_results
- api_debug_logs

## 三、Elasticsearch 索引优先级
- question_index
- search_log_index
- collect_log_index

## 四、Redis Key 上线优先级
- token:user:{id}
- quota:user:{id}
- rate:user:{id}:{api}
- api_key:{api_key}

## 五、迁移执行建议
- 先写 migration，不急着立刻执行
- 等宿主机环境确定后统一跑 migration
- mock 数据可作为初期 seed 参考来源
