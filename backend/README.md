# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **question / search real 分支骨架阶段**：
- `QUESTION_SOURCE` / `LOG_SOURCE` 已可切 `mock | real`
- `QuestionRepository` 已具备 mock/real 双模式结构
- `SearchLogRepository` / `SearchLogDetailRepository` 已具备 mock/real 双模式结构
- `QuotaService` 已预留真实 Redis / MySQL 配额逻辑位置
- 下一步可继续把 SearchService 收敛为 ES 命中 + Mongo 回查的真实流程

## 关键文档
- `project-docs/24-question-search-replacement-plan.md`
