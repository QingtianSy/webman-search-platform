# backend

Webman 后端代码目录。

## 当前已落地
- composer.json 骨架
- config/app.php
- config/routes.php
- config/database.php
- config/redis.php
- config/mongodb.php
- config/elasticsearch.php
- config/jwt.php
- support/helpers.php
- support/Request.php
- support/ResponseCode.php
- support/ApiResponse.php
- support/ClientFactory.php
- support/Pagination.php
- BusinessException
- ExceptionHandler
- RequestIdMiddleware
- DashboardController / DashboardService
- 用户/管理端/开放平台控制器骨架与初版逻辑
- AuthService / JwtService 初版
- SearchService / SearchLogService / QuotaService / LogService 初版
- QuestionService 与 QuestionRepository 列表骨架
- SearchQueryValidator
- Repository 骨架与 API Key 仓储骨架

## 当前状态
当前代码已进入 **第三批真实数据流骨架阶段**：
- 用户登录可返回模拟 token
- 管理员登录可返回模拟 token
- 用户工作台已通过 Service 返回结构化数据
- 搜题接口具备参数校验、额度检查、日志调用、服务调用骨架
- 搜题日志服务已具备主表/明细双写骨架
- 开放平台搜题接口已接入 API Key 鉴权骨架
- 管理端题目列表接口已接入服务层与分页返回
- 健康检查接口可直接使用

## 下一步
- 接入真实 Webman 框架
- 接入真实 JWT 实现
- 接入 MySQL / MongoDB / ES / Redis 客户端
- 替换 AuthService 的模拟登录逻辑
- 替换 SearchService 的模拟搜题逻辑
- 接入真实日志写入与额度扣减
- 实现题目 CRUD 与搜索日志持久化
