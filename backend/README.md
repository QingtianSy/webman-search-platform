# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **question / search 真实替换推进阶段**：
- `QuestionRepository` / `QuestionIndexRepository` / `SearchService` 已具备 mock / real 双模式主干
- real 模式已明确为：ES 命中 -> Mongo 回查 -> MySQL/Mongo 记日志 -> Redis 配额
- 下一步可继续把 real 分支中剩余占位替换为真实连接实现

## 关键文档
- `project-docs/24-question-search-replacement-plan.md`
- `project-docs/29-question-search-code-progress.md`
