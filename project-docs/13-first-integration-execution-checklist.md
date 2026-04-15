# 第一批真接入执行清单

## 目标
本批次只做“框架与基础设施接入准备”，不一次性替换所有 mock 逻辑。

## 本批次要完成
1. `backend/composer.json` 切换到真实依赖声明
2. 宿主机准备 composer install 能力
3. 保留当前目录结构不推翻
4. 明确当前占位文件哪些会被真实 Webman 替换
5. 准备第二批（auth / rbac）接入入口

## 需要重点确认
- PHP 8.2 版本
- Composer 2.x
- Swoole 扩展版本
- Mongo 扩展
- Redis 扩展

## 当前占位文件后续处理
- `backend/start.php`：后续替换为真实 Webman 启动方式
- `backend/support/Request.php`：后续逐步替换为真实 Request
- `backend/bootstrap/*`：后续对接官方结构

## 本批次不做的事
- 不安装数据库
- 不跑 migration
- 不替换业务 Repository
- 不动前端

## 完成标志
- composer 依赖清单确定
- 宿主机依赖准备脚本确定
- 第一批接入边界明确
