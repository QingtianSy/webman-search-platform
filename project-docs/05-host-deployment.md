# 宿主机部署方案

## 一、宿主机标准

### 系统
- Ubuntu 22.04 LTS

### 服务
- Nginx
- PHP 8.2
- Webman 2.x
- Workerman 5.x
- Swoole 5.1.x
- MySQL 8.0
- MongoDB 7
- Elasticsearch 8
- Redis 7.2
- systemd
- logrotate

## 二、目录规划

```bash
/var/www/search-platform/
├── backend/
├── frontend/
├── logs/
├── storage/
├── scripts/
└── backups/
```

## 三、生产要求

### Web
- Nginx 负责 HTTPS、静态资源、反向代理
- Webman 监听内网端口
- systemd 守护 Webman 进程

### 安全
- Redis 不暴露公网
- MongoDB 不暴露公网
- Elasticsearch 不暴露公网
- 数据库仅内网开放
- 密钥与密码不明文提交仓库

### 可观测性
- /health
- /ready
- Nginx access / error 日志
- Webman app / error 日志
- 采集任务日志
- 搜题日志

## 四、运维要求

### 日志切割
- 配置 logrotate
- 定期清理历史日志

### 备份
- MySQL 定时备份
- MongoDB 定时备份
- 关键上传文件备份
- env 与部署脚本备份

### 进程管理
- systemd 自动拉起
- 支持 restart / status / stop

## 五、第一版上线建议

### 初期单机
- 4C8G 起步
- MySQL / MongoDB / Redis / ES 本机部署或内网独立部署
- Nginx + Webman 同机

### 上线顺序
1. 先部署数据库与缓存
2. 再部署 Webman
3. 再部署前端静态资源
4. 配置 Nginx 与 SSL
5. 配置 systemd
6. 做健康检查与回归测试
