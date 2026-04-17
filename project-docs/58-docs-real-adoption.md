# Docs module real adoption notes

## 当前可用脚本
- `scripts/apply_docs_schema.sh`

## 推荐顺序
1. 确认 docs 相关表创建完成
2. 切 `DOCS_SOURCE=real`
3. 验证用户端文档分类/详情/配置
4. 验证管理端文档列表

## 当前说明
文档模块适合作为第三优先级里最先继续真实化的模块之一，因为它同时服务于：
- 用户端文档中心
- 管理端文档管理
