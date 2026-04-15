# 真接入执行批次清单

## 批次 1：框架与基础设施
- 接入 workerman/webman-framework
- 接入 workerman/workerman
- 正式 Request / Response
- 正式路由注册
- 正式 middleware 注册
- 正式 .env 加载

## 批次 2：统一认证与 RBAC
- users
- roles
- permissions
- user_role
- role_permission
- menus
- 统一登录 / profile / menus / permissions

## 批次 3：题库与搜索主线
- questions -> MongoDB
- question_index -> Elasticsearch
- 题目列表 / 详情
- 搜题接口

## 批次 4：日志与额度
- search_logs -> MySQL
- search_log_details -> MongoDB
- quota -> Redis / MySQL
- health / ready 真接入

## 批次 5：用户中心与开放平台
- 钱包
- 套餐
- API Key
- 文档
- 开放平台 quota

## 批次 6：采集与配置
- 采集任务
- 接口源
- 系统配置
- 公告
- 文档管理

## 原则
- 每批次都要可回归测试
- 每批次只替换一个清晰边界
- 每批次替换完成后再删除对应 mock 依赖
