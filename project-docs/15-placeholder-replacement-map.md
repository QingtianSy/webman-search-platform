# 占位文件替换映射表

## 一、启动入口
### 当前
- `backend/start.php`

### 后续
- 替换为真实 Webman 启动入口
- 与 `workerman/webman-framework` 的官方结构对齐

### 说明
当前 `start.php` 只是为了固定宿主机运行约定，不应长期保留为最终实现。

---

## 二、Request/Response 适配
### 当前
- `backend/support/Request.php`
- `backend/support/ApiResponse.php`

### 后续
- `support/Request.php` 逐步替换为真实 Webman Request 使用方式
- `ApiResponse.php` 保留为项目统一返回适配层，但内部可逐步接 Webman Response/Json 输出

### 说明
不要一次性删除当前适配层，建议先兼容，再逐步迁移调用。

---

## 三、bootstrap
### 当前
- `backend/bootstrap/app.php`
- `backend/bootstrap/routes.php`

### 后续
- 按 Webman 官方推荐结构接管配置加载与路由加载

### 说明
当前 bootstrap 的核心价值是固定目录与职责边界，后续应保留思想、替换实现。

---

## 四、中间件注册
### 当前
- `backend/config/middleware.php`
- 自定义 User/Admin/Open 中间件

### 后续
- 改成真实 Webman 中间件注册方式
- 中间件类可保留，注册方式替换

---

## 五、mock Repository
### 当前
- `backend/app/repository/*` 中大量 JSON 文件读取实现

### 后续
- MySQL: users / roles / permissions / menus / wallets / subscriptions / announcements / configs
- MongoDB: questions / search_log_details / collect_raw_results
- ES: question_index / search_log_index / collect_log_index
- Redis: token / quota / rate limit / api_key

### 说明
Repository 是最优先替换点，Controller 与 Service 尽量不大动。

---

## 六、前端 API 对接
### 当前
- `/api/v1/auth/*`
- `/api/v1/user/*`
- `/api/v1/admin/*`
- `/open/v1/*`

### 后续
- 路径尽量保持不变
- 后端替换实现，前端少改

---

## 七、替换原则
1. 先替换底层实现，不先改接口路径
2. 先替换认证主线，再替换题库/搜题主线
3. mock 文件删除必须在对应 real repository 验证后进行
4. 所有替换要保持用户端、管理端、开放平台三条主线稳定
