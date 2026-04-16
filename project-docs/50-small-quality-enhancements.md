# 小范围质量增强说明

## 本轮内容
- 为 `QuestionIndexRepository::searchReal()` 增加异常日志落盘
- 新增 `search_real_smoke.php`，用于保留 real 模式最小调用自检入口
- 更新测试说明，明确 mock smoke 与 real smoke 的边界

## 目的
在不大改结构的前提下，提升 real 分支调试能力与代码侧质量。
