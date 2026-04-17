# Collect module real adoption notes

## 当前可用脚本
- `scripts/apply_collect_schema.sh`

## 推荐顺序
1. 创建 `collect_accounts` / `collect_tasks` 真表
2. 切 `COLLECT_SOURCE=real`
3. 验证用户端 `/api/v1/user/collect/task/list`
4. 验证管理端 `/api/v1/admin/collect/task/list`

## 当前说明
当前只处理采集外围（账号/任务/详情）这类可结构化部分；采集核心执行脚本仍属于后续范围。
