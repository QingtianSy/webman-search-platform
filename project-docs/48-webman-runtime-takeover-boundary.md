# Webman 官方运行态接管边界

## 一、当前占位层（后续会被官方运行态接管）

### `start.php`
- 当前：占位命令入口
- 后续：由官方 Webman 启动逻辑接管

### `public/index.php`
- 当前：占位提示入口
- 后续：由官方 Webman 公开入口接管

### `support/Request.php`
- 当前：已删除自定义兼容层，直接使用官方
- 后续：由官方 Webman Request 完全接管

### `support/Response.php`
- 当前：已删除自定义兼容层
- 后续：由官方 Webman Response 完全接管

### `support/bootstrap.php`
- 当前：占位
- 后续：由官方启动后初始化逻辑接管

## 二、当前配置层（后续需要填充真实内容）

### `config/exception.php`
- 当前：已指向 `app\exception\ExceptionHandler`
- 后续：按官方异常处理方式完善

### `config/log.php`
- 当前：已指向 `runtime/logs/app.log`
- 后续：按官方日志配置完善

### `config/process.php`
- 当前：已注册 Http / Monitor 占位进程
- 后续：按官方自定义进程方式完善

## 三、当前保留层（不会被官方接管，属于业务扩展）

### `app/service`
### `app/repository`
### `app/validate`
### `app/common`
### `database/migrations`
### `database/seeds`
### `tests`

这些属于业务项目扩展层，官方不强制要求，但对当前项目有价值。

## 四、结论
后续真接入时，优先让官方接管"占位层"，保留"业务扩展层"。
