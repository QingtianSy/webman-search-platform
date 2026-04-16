# 小范围质量增强说明

## 本轮新增
- 新增 `scripts/check_backend_routes.sh`
- 将后端路由目标存在性检查接入质量门禁
- 调整质量检查策略：在 Minis 环境下优先使用轻量门禁

## 当前推荐门禁顺序
1. `composer smoke`
2. `scripts/check_docs_clean.sh`
3. `scripts/check_backend_routes.sh`

## 原因
全仓库逐文件 lint 在 Minis / iSH 环境下成本较高，容易在体感上出现“卡住”。
轻量门禁更适合作为当前阶段的持续检查方式。
