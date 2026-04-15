# 项目最终收口清单

## 一、已完成部分

### 1. 架构与文档
- 项目总方案
- 数据库设计
- API 设计
- Mock → Real 替换路线
- Migration 规划
- Webman 真接入准备
- 真接入执行总清单
- auth/rbac 替换方案
- question/search 替换方案

### 2. 后端骨架
- 统一用户体系（一个 users 模型思路）
- RBAC 模型（roles / permissions / menus）
- 统一认证入口 `/api/v1/auth/*`
- 用户端接口骨架
- 管理端接口骨架
- 开放平台接口骨架
- 健康检查 `/health` / `/ready`
- mock / real 双模式切换准备（auth / rbac、question / search）
- PDO / Mongo / ES / Redis 适配层骨架

### 3. 前端骨架
- 一个前端项目
- 一个统一登录页
- 统一布局壳
- auth store
- 路由守卫
- 动态菜单预留
- 用户端核心页面骨架
- 管理端核心页面骨架
- 表格 / 卡片 / 表单交互骨架

### 4. 运维与宿主机方案
- Nginx 模板
- systemd 模板
- shell 运维脚本
- Python 巡检脚本
- backend 运行时检查脚本
- backend 依赖准备脚本

---

## 二、当前仍是 mock / 占位的部分

### 1. 后端真实数据仍未接入
- users / roles / permissions / menus 仍是 mock JSON 为主
- questions 仍是 mock JSON 为主
- search_logs 仍是本地 jsonl 为主
- quota 仍是占位逻辑
- 公告 / 文档 / 系统配置 / 采集任务 / API Key 仍是 mock 数据源

### 2. 框架真实入口仍未完全接管
- `start.php` 仍是 placeholder 入口
- `public/index.php` 仍是 placeholder 入口
- `support/Request.php` / `ApiResponse.php` 仍是兼容层

### 3. 前端仍未正式接入 Vben Admin 官方结构
- 当前是按 Vben Admin + Naive UI 的项目思路搭骨架
- 尚未完整切入官方 Vben 模板体系

---

## 三、现在已经可以开始真接入的部分

### 第一优先级：auth / rbac
可以立刻开始：
1. 建 `users / roles / permissions / user_role / role_permission / menus`
2. 导入 seed
3. `AUTH_RBAC_SOURCE=real`
4. 替换 auth 相关 Repository real 分支
5. 验证统一登录与权限菜单

### 第二优先级：question / search
在 auth/rbac 跑通后立刻开始：
1. questions -> MongoDB
2. question_index -> Elasticsearch
3. search_logs -> MySQL
4. search_log_details -> MongoDB
5. quota -> Redis
6. 验证搜题主线与 open API

---

## 四、部署前最后准备项

### 1. 必须先完成
- auth/rbac 真接入
- question/search 真接入
- 用户工作台真实数据接入
- API Key 真接入
- 公告 / 系统配置 / 文档 / 采集任务至少完成真实主表接入

### 2. 前端至少要完成
- 统一登录页可正常登录
- 工作台页可正常加载真实数据
- 管理端题目列表可加载真实数据
- 用户端搜题日志页可加载真实数据
- 动态菜单由后端返回驱动

### 3. 部署前检查
- composer install 正常
- PHP 扩展齐全
- MySQL / MongoDB / Redis / Elasticsearch 服务可用
- systemd / Nginx 配置可加载
- `/health` 和 `/ready` 正常

---

## 五、建议的下一步顺序

### 推荐顺序
1. 执行 Phase 1：依赖准备
2. 执行 Phase 2：auth/rbac 真接入
3. 执行 Phase 3：question/search 真接入
4. 回头补用户中心与配置模块真实数据
5. 再统一做部署搭建与联调

---

## 六、当前结论

### 可以明确说：
项目已经不是“从零开始”的阶段，
而是已经进入：

# 可开始真实接入执行阶段

### 还不建议直接做最终部署的原因：
因为：
- 真实数据层还没完全接管
- 前端还没完成真实接口联调
- 采集核心执行脚本还没接入

### 但完全可以开始：
- auth/rbac 真接入
- question/search 真接入

这两步做完后，项目就会真正进入上线前联调阶段。
