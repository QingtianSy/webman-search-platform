# Webman 真接入准备说明

## 一、当前阶段
当前仓库是“生产级结构 + mock 业务数据源”阶段，还没有正式拉入 Webman 官方完整运行时。

## 二、接入目标
后续要完成：
- 引入 `webman/framework`
- 引入 `workerman/workerman`
- 接入 Swoole 扩展
- 使用真实 Webman 路由 / 中间件 / 配置加载方式
- 用真实 Request / Response 替换当前轻量占位

## 三、接入步骤建议

### 1. 安装框架依赖
- webman/framework
- 需要的中间件/ORM/Redis/Mongo/ES 客户端

### 2. 保留现有目录结构
当前 app/config/support 目录已经尽量按 Webman 风格收束，正式接入时不建议推倒重来。

### 3. 替换占位能力
重点替换：
- `support/Request.php`
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

## 四、接入前不要做的事
- 不要继续扩大 mock 文件数量到无法维护
- 不要让 controller 里堆积复杂逻辑
- 不要在 mock 阶段写死太多路径/结构

## 五、接入后优先验证
1. 统一登录
2. 统一 profile / menus / permissions
3. 题目列表
4. 搜题接口
5. 搜题日志
6. 工作台
