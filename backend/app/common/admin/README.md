# admin/common

后台管理模块公共支撑层。

## 当前用途
- `AdminListBuilder`：统一后台列表分页返回结构
- `AdminId`：统一后台 ID 参数解析

## 目的
让后台模块继续向 webman-admin 风格收口时，避免重复写相同的列表/参数逻辑。
