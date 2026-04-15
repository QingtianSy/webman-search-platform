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
- BusinessException
- RequestIdMiddleware
- HealthController
- 用户/管理端/开放平台控制器骨架
- AuthService / JwtService 初版
- SearchService / QuotaService 初版
- Repository 骨架

## 当前状态
当前代码是 **生产级项目的第一批可运行逻辑骨架**：
- 用户登录可返回模拟 token
- 管理员登录可返回模拟 token
- 搜题接口具备参数校验、额度检查、服务调用骨架
- 健康检查接口可直接使用

## 下一步
- 接入真实 Webman 框架
- 接入真实 JWT 实现
- 接入 MySQL / MongoDB / ES / Redis 客户端
- 替换 AuthService 的模拟登录逻辑
- 替换 SearchService 的模拟搜题逻辑
- 接入真实日志写入与额度扣减
