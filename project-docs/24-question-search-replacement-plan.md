# Question / Search 主线真实替换方案

## 一、目标
将当前基于 mock questions.json + 本地日志文件的搜题主线，替换为真实：
- MongoDB questions
- Elasticsearch question_index
- MySQL search_logs
- MongoDB search_log_details
- Redis quota

## 二、当前需要替换的类
- `QuestionRepository`
- `SearchService`
- `SearchLogRepository`
- `SearchLogDetailRepository`
- `QuotaService`

## 三、替换思路
### Step 1
先切换 `QuestionRepository`：
- 列表与详情支持真实 Mongo 查询
- 搜索结果优先由 ES 提供，不再依赖 Mongo 全文遍历

### Step 2
切换 `SearchService`：
- 搜索逻辑：ES 命中 -> Mongo 回查
- 没命中时再考虑外部接口 / AI 兜底

### Step 3
切换日志：
- 主日志 -> MySQL
- 明细日志 -> MongoDB

### Step 4
切换额度：
- Redis quota key
- MySQL quota_logs 持久化

## 四、当前切换开关
- `QUESTION_SOURCE=mock|real`
- `LOG_SOURCE=mock|real`

## 五、注意事项
- 题目完整数据仍以 MongoDB 为主
- ES 只做搜索索引
- Quota 不要直接只依赖 MySQL
