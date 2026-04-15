# Docs / Config / Collect 代码推进说明

## 本轮新增的代码层改进
1. `DocCategoryRepository` 已支持 mock / real 双模式
2. `DocArticleRepository` 已支持 mock / real 双模式
3. `CollectAccountRepository` 已支持 mock / real 双模式
4. `CollectTaskRepository` 已支持 mock / real 双模式
5. `ApiSourceRepository` 已支持 mock / real 双模式
6. `integration.php` 已新增：
   - `DOCS_SOURCE`
   - `COLLECT_SOURCE`
   - `CONFIG_SOURCE`
   - `API_SOURCE_SOURCE`

## 这样做的意义
- 文档中心、系统配置、接口源、采集外围都已经具备真实替换的落点
- 未来开始真接入时，不需要再临时设计这些模块的数据切换方式

## 下一步建议
1. 需要时再为 system_configs / doc_config / collect_task_details 单独补更细的 real 分支
2. 真接入优先级仍应低于 auth/rbac 与 question/search
