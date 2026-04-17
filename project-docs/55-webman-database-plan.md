# 引入 webman/database 方案

## 结论
当前项目**值得引入 `webman/database`**，但仅限：
- MySQL 后台管理类模块

## 适合优先 ORM 化的模块
- 用户管理
- 角色管理
- 权限管理
- 菜单管理
- 公告管理
- 系统配置
- 套餐管理
- API Key 管理
- 钱包/用户中心基础

## 不建议 ORM 化的模块
- 搜题主线（Mongo/ES/Redis 多数据源）
- 采集外围与未来采集脚本链
- 开放平台
- 搜题日志明细

## 当前策略
- 先引入 `webman/database`
- 后台管理模块逐步从 Repository 过渡到 Model + Service + Validate
- 不强制一次性把所有模块改成 ORM

## 推荐节奏
1. 先接入依赖与配置
2. 先补 admin model 基础
3. 先重构用户/角色/权限/菜单 4 个模块
4. 再逐步推进公告/配置/套餐
