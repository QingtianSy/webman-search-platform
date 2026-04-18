# infra

宿主机部署配置模板与 systemd / nginx 配置目录。

## 当前文件
- `nginx.search-platform.conf`
- `webman-search-platform.service`

## 用法
### Nginx
将 `nginx.search-platform.conf` 复制到：
- Ubuntu: `/etc/nginx/sites-available/`
然后软链到：
- `/etc/nginx/sites-enabled/`

### systemd
将 `webman-search-platform.service` 复制到：
- `/etc/systemd/system/`
然后执行：
- `systemctl daemon-reload`
- `systemctl enable webman-search-platform`
- `systemctl start webman-search-platform`
