# frontend

统一前端项目骨架，当前明确收口方向为：
- **Vben Admin 思路**
- **Naive UI**
- 一个登录页
- 一个前端项目
- 统一用户体系
- 按角色和权限动态决定进入用户端或管理端模块

## 当前说明
- 已在 `package.json` 中补入 `@vben-core/shared`
- 当前仍保持单 frontend 结构，不采用官方 monorepo 目录
- 原因：当前业务项目更适合单前端结构，但会继续吸收 Vben 的布局/菜单/权限组织思路
