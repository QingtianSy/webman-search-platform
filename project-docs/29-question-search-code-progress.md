# Question / Search 代码推进说明

## 本轮新增的代码层改进
1. `QuestionRepository` 已支持 `QUESTION_SOURCE=mock|real`
2. `QuestionIndexRepository` 已具备 real 分支查询骨架
3. `SearchService` 已拆分 `queryMock()` 与 `queryReal()`
4. `QuestionService` 已补 `findManyByIds()`，为 ES 命中后回查 Mongo 预留接口
5. `SearchLogRepository` / `SearchLogDetailRepository` / `QuotaService` 已具备 mock / real 双模式结构

## 当前真实流程设计
### mock 模式
- QuestionRepository 本地搜索
- 本地日志
- 本地额度占位

### real 模式
- ES 命中 question_id
- QuestionService 回 Mongo 查完整题目
- SearchLog 主表 -> MySQL
- SearchLog 明细 -> MongoDB
- Quota -> Redis / MySQL

## 下一步建议
1. 补真实 Mongo / ES 客户端接入实现
2. 替换 SearchService real 分支中的空列表返回
3. 在宿主机环境可用后切 `QUESTION_SOURCE=real`
