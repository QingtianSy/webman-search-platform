# 后台模块当前落地情况

## 已较完整落地规范的模块
- 用户管理
- 角色管理
- 权限管理
- 菜单管理
- 公告管理
- 系统配置
- 套餐管理
- 文档管理
- 采集管理
- 接口源管理
- 搜题日志管理
- 题目管理
- 题目字典（分类 / 类型 / 来源 / 标签）

## 已具备的统一能力
- AdminQuery / AdminQueryValidate
- AdminListBuilder
- AdminStatusFilter
- AdminSort
- AdminTimeRange
- 动作返回协议逐步统一
- Model 驱动第一阶段逐步推进

## 当前仍需继续完善的方向
- 部分模块 detail / create / update / delete 的返回风格继续统一
- 后台 ORM 第一阶段继续从“读”推进到“读写都统一”
- 少量稳定优先版 controller 后续继续回收完整链
