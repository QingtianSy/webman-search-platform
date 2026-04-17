# Api source module real adoption notes

## 当前可用脚本
- `scripts/apply_api_source_schema.sh`

## 推荐顺序
1. 创建 `api_sources` 真表
2. 切 `API_SOURCE_SOURCE=real`
3. 验证 `/api/v1/admin/api-source/list`
4. 再验证 `/api/v1/admin/api-source/detail` 与 `/api/v1/admin/api-source/test`

## 当前说明
接口源模块是典型后台管理模块，适合作为第三优先级里较容易切真的模块。
