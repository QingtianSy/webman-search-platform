# Config module real adoption notes

## 当前状态
- `SystemConfigRepository` 已支持 `CONFIG_SOURCE=mock|real`
- `SystemConfigController` / `SystemConfigAdminService` 已具备 real-ready 路径

## 下一步
1. 补 `system_configs` 真表 schema（若服务器尚未建表）
2. 切 `CONFIG_SOURCE=real`
3. 验证 `/api/v1/admin/system-config/list`
4. 验证 `/api/v1/admin/system-config/update`
