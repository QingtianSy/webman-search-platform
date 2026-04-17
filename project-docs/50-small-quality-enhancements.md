# 小范围质量增强说明

## 已完成
- 为 `QuestionIndexRepository::searchReal()` 增加异常日志落盘
- 新增 `search_real_smoke.php`
- 新增 `check_backend_routes.sh`
- 建立轻量质量门禁策略
- 新增 `user_center_real_ready_smoke.php`
- 新增 `check_critical_files.sh`
- 为 `AuthService::profile()` 增加可选 Redis 缓存

## 当前价值
这些增强不改变主结构，但能提升：
- 代码侧自检能力
- 小步修改后的回归确认能力
- 关键链路的性能与调试能力
