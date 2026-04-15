# 项目总方案

## 一、项目定义

本项目定义为 **题库搜题 SaaS 平台**。

包含两个端：

1. 用户端
2. 管理端

包含六个业务中心：

1. 用户中心
2. 搜题中心
3. 题库中心
4. 采集中心
5. 开放平台中心
6. 运营管理中心

## 二、核心闭环

第一版最小可上线闭环：

1. 用户登录
2. 题目录入/导入
3. MongoDB 存储完整题目
4. Elasticsearch 建立搜索索引
5. 搜题接口查询 ES
6. 通过 question_id 回 MongoDB 查完整题目
7. Redis 扣减额度
8. MySQL 记录搜题日志索引
9. MongoDB 记录搜题日志详情
10. 前端展示结果和日志

## 三、技术选型

### 后端
- PHP 8.2
- Webman 2.x
- Workerman 5.x
- Swoole 5.1.x

### 数据层
- MySQL 8.0
- MongoDB 7
- Elasticsearch 8
- Redis 7.2

### 前端
- Node.js 20 LTS
- Vue 3
- Vite 5
- TypeScript 5
- Vben Admin
- Naive UI

### 部署
- Ubuntu 22.04 LTS
- Nginx
- systemd
- 宿主机直装

## 四、数据职责

### MySQL
- 用户
- 权限
- 菜单
- 套餐
- 订单
- 钱包
- API Key
- 文档
- 系统配置
- 采集任务主表
- 日志索引

### MongoDB
- 完整题目
- 原始采集结果
- 搜题日志明细
- 调试数据
- 大字段日志

### Elasticsearch
- 题目搜索索引
- 搜题日志索引
- 采集日志索引
- API 调用日志索引

### Redis
- token
- 权限缓存
- 额度缓存
- 限流
- 热搜
- 搜题结果缓存
- 任务状态

## 五、开发顺序

### Phase 1：基础底座
- Webman 初始化
- env 规范
- MySQL/MongoDB/Redis/ES 连接
- JWT
- 统一返回结构
- 全局异常处理
- 健康检查接口

### Phase 2：搜题核心闭环
- 题库管理
- MongoDB 入库
- ES 同步
- 搜题接口
- Redis 扣次
- 搜题日志

### Phase 3：用户中心
- 个人中心
- 我的套餐
- API Key
- 调用记录
- 通知公告

### Phase 4：采集中心
- 采集账号
- 采集任务
- 原始结果
- 课程采集
- 数据清洗与入库

### Phase 5：开放平台与商业化
- 对接文档
- Header / URL 鉴权
- IP 白名单
- 在线充值
- SDK 示例
- 调用统计
