# 后台模块 ORM 化准备说明

## 当前判断
后台管理类 MySQL 模块适合逐步引入 `webman/database`，但不应一次性全量切换。

## 第一批最适合 ORM 化的模块
- 用户管理
- 角色管理
- 权限管理
- 菜单管理

## 第二批适合 ORM 化的模块
- 公告管理
- 系统配置
- 套餐管理
- API Key 管理
- 钱包

## 当前策略
- 先保留 Repository 可用状态
- 同时准备 `app/model/admin/*`
- 后续逐模块把 service 改成：优先 Model，必要时保留 Repository

## 不建议 ORM 化的模块
- 搜题主线（Mongo / ES / Redis）
- 采集外围及未来采集脚本链
- 开放平台查询链路
