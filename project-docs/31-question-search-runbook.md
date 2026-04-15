# Question / Search 真实接入执行脚本说明

## 当前可用脚本
- `scripts/apply_search_logs_schema.sh`
- `scripts/prepare_question_index.sh`
- `scripts/check_search_stack.sh`

## 推荐执行顺序
1. 确认 MongoDB / Elasticsearch / Redis 可用
2. 执行 `scripts/check_search_stack.sh`
3. 执行 `scripts/apply_search_logs_schema.sh`
4. 执行 `scripts/prepare_question_index.sh`
5. 切 `QUESTION_SOURCE=real`
6. 切 `LOG_SOURCE=real`
7. 验证：
   - `/api/v1/user/search/query`
   - `/api/v1/user/search/logs`
   - `/open/v1/search/query`
   - `/open/v1/quota/detail`

## 说明
当前 questions 集合和 search_log_details 集合的真实接入仍按 MongoDB 集合方式进行，不通过 SQL 文件创建。
