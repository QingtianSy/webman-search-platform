# Phase 1 真实依赖接入执行文档

## 一、目标
本阶段目标不是替换业务逻辑，而是让 backend 具备：
- 真依赖可安装
- 真入口可占位
- 宿主机可检查运行时条件
- 后续 auth/rbac 替换可以顺利开始

## 二、当前已验证结果
已在当前可用环境中验证：
- `composer.json` 依赖解析通过
- `mongodb` 扩展可用
- `workerman/webman-framework` 包名已修正并可被 Composer 解析
- `composer install --dry-run` 可正常生成依赖计划

## 三、执行顺序

### 1. 宿主机检查
执行：
- `scripts/check_backend_runtime.sh /var/www/search-platform/backend`

确认：
- php 存在
- composer 存在
- composer.json 正常
- .env 已存在

### 2. 准备依赖
执行：
- `scripts/prepare_backend_dependencies.sh`

目标：
- 生成 `vendor/autoload.php`

### 3. 检查入口
确认：
- `backend/public/index.php`
- `backend/start.php`

当前行为：
- 若依赖未安装，返回明确错误提示
- 若依赖已安装，进入占位可运行状态

### 4. 不做的事
- 不替换业务 Repository
- 不改前端
- 不跑 migration
- 不接数据库

## 四、完成标志
- composer install 可执行
- vendor/autoload.php 可生成
- public/index.php 可输出占位 JSON
- start.php 可跑 placeholder command
