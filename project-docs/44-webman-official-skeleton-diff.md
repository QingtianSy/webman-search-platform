# 官方 Webman 骨架差异分析

## 官方骨架核心目录
官方 Webman 项目骨架核心包括：
- `app/controller`
- `app/model`
- `app/view`
- `app/middleware`
- `app/process`
- `config/*`
- `public`
- `runtime`
- `support/Request.php`
- `support/Response.php`
- `support/bootstrap.php`

## 当前项目相对官方的情况
### 已基本对齐
- `app/controller`
- `app/middleware`
- `public`
- `runtime`
- `support`
- `config/*` 官方推荐文件已补占位
- `support/Request.php` 已恢复为主兼容层
- `support/Response.php` / `support/bootstrap.php` 已补齐
- `app/process` / `app/model` / `app/view` 已补目录占位

### 业务扩展层（保留）
当前项目额外保留：
- `app/service`
- `app/repository`
- `app/validate`
- `app/common`

原因：
这些属于业务项目常见扩展层，对当前项目有价值，不建议为了完全像官方而删除。

## 结论
### 前端
不建议按官方 Vben monorepo 推倒重来。

### 后端
应继续以官方 Webman 目录结构为主骨架，在此基础上保留必要业务扩展层。

## 当前决策
- **不推倒当前项目结构**
- **后端继续向官方 Webman 结构收口**
- **前端继续保持当前 frontend 单项目结构**
