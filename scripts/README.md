# scripts

辅助脚本目录，后续放置部署脚本、备份脚本、巡检脚本。

## 当前 Shell 脚本
- `backup_mysql.sh`：MySQL 备份脚本
- `backup_mongo.sh`：MongoDB 备份脚本
- `deploy_backend.sh`：后端宿主机重启部署脚本
- `health_check.sh`：基础健康检查脚本

## 当前 Python 脚本
- `check_health.py`：结构化健康检查，适合 CI / 巡检
- `check_services.py`：systemd 服务状态巡检
- `report_status.py`：汇总健康检查、服务状态、磁盘状态

## 生产级建议
- **简单入口脚本保留 shell**：部署、备份、快速检查
- **复杂巡检与汇总使用 Python**：更适合 JSON、错误处理、报告输出

## 建议后续补充
- `deploy_frontend.sh`
- `rotate_logs.sh`
- `restore_mysql.sh`
- `restore_mongo.sh`
- `notify_status.py`
