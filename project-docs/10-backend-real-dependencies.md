# 后端真实依赖清单（计划）

## 一、核心框架
- `webman/framework`
- `workerman/workerman`

## 二、环境与工具
- `vlucas/phpdotenv`
- `monolog/monolog`

## 三、鉴权
- JWT 相关实现（后续按实际方案选型）

## 四、数据库 / 存储
### MySQL
- ORM / DBAL（按 Webman 最终选型）

### Redis
- ext-redis 或兼容客户端

### MongoDB
- `mongodb/mongodb`
- PHP Mongo 扩展

### Elasticsearch
- `elasticsearch/elasticsearch`

## 五、HTTP 请求
- `guzzlehttp/guzzle`

## 六、推荐原则
- 先最小可运行依赖
- 不一次性引入过多插件
- 先 auth / rbac / question / search / logs 主线
- 其余模块按需补充
