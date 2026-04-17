# admin 模块化重构说明

## 本轮目标
- 后台管理控制器继续变薄
- 业务列表逻辑下沉到 `app/service/admin/*`
- 更贴 webman-admin 的后台组织方式

## 当前已完成
- 用户管理服务
- 角色管理服务
- 权限管理服务
- 菜单管理服务
- 对应控制器已改为仅调 Service

## 后续建议
- 继续把公告/系统配置/套餐管理也迁到 `service/admin/*`
- 逐步补后台 validate / model
