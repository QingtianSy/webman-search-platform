# scripts

辅助脚本目录，后续放置部署脚本、备份脚本、巡检脚本。

## 当前 Shell 脚本
- `backup_mysql.sh`：MySQL 备份脚本
- `backup_mongo.sh`：MongoDB 备份脚本
- `deploy_backend.sh`：后端宿主机重启部署脚本
- `health_check.sh`：基础健康检查脚本
- `prepare_backend_dependencies.sh`：后端真实依赖准备脚本
- `check_backend_runtime.sh`：后端运行时条件检查脚本
- `check_auth_rbac_db.sh`：auth/rbac 数据库连通检查
- `apply_auth_rbac_schema.sh`：auth/rbac 表结构执行
- `apply_auth_rbac_seed.sh`：auth/rbac seed 执行

## 当前 Python 脚本
- `check_health.py`：结构化健康检查，适合 CI / 巡检
- `check_services.py`：systemd 服务状态巡检
- `report_status.py`：汇总健康检查、服务状态、磁盘状态
