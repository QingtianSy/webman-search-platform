# webman-search-platform

一个按 **Webman + Swoole + MySQL + MongoDB + Elasticsearch + Redis** 方向构建的题库搜题平台项目。

## 当前阶段
项目已进入：
- 后端真实主线已打通（auth/rbac、question/search、user-center 基础）
- 后端继续按官方 Webman 结构收口
- 前端已决定推倒重构为 **官方 Vben 完整基座**

## 当前前端策略
- `frontend/`：官方 Vben 完整基座
- `frontend-legacy/`：保留旧前端作为业务迁移参考

## 当前结论
后续前端不再在旧骨架上继续修补，而是在官方 Vben 基座上逐步迁业务。
