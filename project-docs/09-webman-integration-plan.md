# Webman 真接入准备说明

## 一、当前阶段
当前仓库已经从“纯文档准备”进入到“代码级替换准备”阶段：
- `start.php` 已作为未来真实入口替换点
- `support/InputRequest.php` 已作为兼容层保留
- `support/ApiResponse.php` 已作为兼容层保留
- `bootstrap/*` 已标明未来接管边界
- `public/index.php` 已预留

## 二、接入目标
后续要完成：
- 引入 `workerman/webman-framework`
- 引入 `workerman/workerman`
- 接入 Swoole 扩展
- 使用真实 Webman 路由 / 中间件 / 配置加载方式
- 用真实 Request / Response 替换当前轻量占位

## 三、接入步骤建议

### 1. 安装框架依赖
- workerman/webman-framework
- 需要的中间件/ORM/Redis/Mongo/ES 客户端

### 2. 保留现有目录结构
当前 app/config/support 目录已经尽量按 Webman 风格收束，正式接入时不建议推倒重来。

### 3. 替换占位能力
重点替换：
- `support/InputRequest.php`
- `start.php`
- `bootstrap/*`
- middleware 注册方式
- response 返回方式

### 4. 接入真实连接
- MySQL
- Redis
- MongoDB
- Elasticsearch

### 5. 逐模块替换 mock repository
按《07-mock-to-real-plan.md》顺序替换。
