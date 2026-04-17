# app/model/admin

后台管理模块模型层。

## 当前状态
- 目前仍以 Repository 为主，Model 先作为 ORM 化准备层
- 后续若引入 `webman/database`，优先让用户/角色/权限/菜单 4 个模块先切换

## 当前原则
- 不急于一次性 ORM 化全部后台模块
- 先把模型层命名和边界收起来
- 再逐模块切换
