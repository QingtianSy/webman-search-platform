# frontend

当前 `frontend/` 已切换为：
# **官方 Vben Admin 完整基座（monorepo）**

## 当前状态
- `frontend-legacy/`：保留此前自研前端实现，作为业务页面迁移参考
- `frontend/`：采用官方 Vben 仓库完整结构

## 后续策略
1. 不再继续迭代 `frontend-legacy/`
2. 在新的官方 Vben 基座上逐步迁移我们的业务：
   - 登录
   - dashboard
   - question
   - logs
   - api-key
   - billing
   - docs
   - collect
   - admin pages
3. 迁移过程中优先保持后端接口不变
