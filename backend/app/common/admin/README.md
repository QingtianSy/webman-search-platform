# admin/common

后台管理模块公共支撑层。

## 当前用途
- `AdminListBuilder`：统一后台列表分页返回结构
- `AdminId`：统一后台 ID 参数解析
- `AdminPage`：统一后台分页参数解析
- `AdminQuery`：统一后台查询参数解析（keyword/status/page/page_size）

## 目的
让后台模块继续向 webman-admin 风格收口时，避免重复写列表/ID/分页/查询条件逻辑。
