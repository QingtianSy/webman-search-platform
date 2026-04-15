# 宿主机部署执行步骤

## 1. 系统准备
- Ubuntu 22.04 LTS
- 创建项目目录：`/var/www/search-platform/`
- 创建运行用户：`www-data`

## 2. 代码目录
- `/var/www/search-platform/backend`
- `/var/www/search-platform/frontend`
- `/var/www/search-platform/logs`
- `/var/www/search-platform/storage`
- `/var/www/search-platform/backups`

## 3. 安装服务
- Nginx
- PHP 8.2
- Redis 7.2
- MySQL 8.0
- MongoDB 7
- Elasticsearch 8

## 4. 配置步骤
1. 复制 `.env.example` 为 `.env`
2. 按宿主机环境填写数据库与缓存配置
3. 将 `infra/nginx.search-platform.conf` 放到 Nginx 站点目录
4. 将 `infra/webman-search-platform.service` 放到 `/etc/systemd/system/`
5. `systemctl daemon-reload`
6. `systemctl enable webman-search-platform`
7. `systemctl start webman-search-platform`

## 5. 备份
- 使用 `scripts/backup_mysql.sh`
- 使用 `scripts/backup_mongo.sh`
- 配合 crontab 做定时备份

## 6. 生产要求
- Redis/Mongo/ES 不直接暴露公网
- 统一由 Nginx 反向代理入口
- HTTPS 后续接入证书
