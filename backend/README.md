# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 骨架
- start.php 启动入口骨架
- bootstrap/app.php
- bootstrap/routes.php
- config/app.php
- config/routes.php
- config/middleware.php
- config/database.php
- config/redis.php
- config/mongodb.php
- config/elasticsearch.php
- config/jwt.php
- .env.production.example
- support/helpers.php
- support/Request.php
- support/ResponseCode.php
- support/ApiResponse.php
- support/ClientFactory.php
- support/Pagination.php
- support/adapter/* 适配层骨架
- BusinessException
- ExceptionHandler
- RequestIdMiddleware
- User/Admin/Open 鉴权中间件
- DashboardController / DashboardService
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表骨架
- SearchQueryValidator
- Repository 骨架与 API Key 仓储骨架
- HealthService 健康检查服务

## 当前状态
当前代码已进入 **生产级接入准备阶段**：
- 已有启动入口和 bootstrap 结构
- 已有 middleware 分组配置
- 已有健康检查与 readiness 检查
- 已有多存储适配层骨架
- 已有宿主机部署脚本与生产环境变量模板

## 下一步
- 接入真实 Webman 官方框架文件
- 接入真实 JWT 实现
- 接入 MySQL / MongoDB / ES / Redis 客户端
- 替换 AuthService 的模拟登录逻辑
- 替换 SearchService 的模拟搜题逻辑
- 接入真实日志写入与额度扣减
- 实现题目 CRUD 与搜索日志持久化
