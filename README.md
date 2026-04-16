# webman-search-platform

一个按生产级思路推进的：
- Webman / Workerman 后端
- Vben Admin + Naive UI 风格前端
- 统一用户体系（users + roles + permissions + menus）
- 题库 / 搜题 / 用户中心 / 开放平台 / 管理后台

## 当前阶段
当前项目已进入：
**代码侧生产级方案基本成型，且后端 smoke tests 可运行通过**

## 已完成的核心能力
- 统一用户体系与 RBAC
- 用户端 / 管理端 / 开放平台骨架
- question/search 主线 mock/real 双模式准备
- user-center / docs / config / collect 外围 real-ready
- Webman 官方结构进一步收口
- 前端统一布局、页面骨架与交互层
- auth/rbac 与 question/search 可执行脚本链
- 代码侧 smoke tests

## 关键文档
- `project-docs/25-real-integration-master-checklist.md`
- `project-docs/27-project-final-convergence-checklist.md`
- `project-docs/39-official-doc-alignment-review.md`
- `project-docs/43-official-doc-final-decision.md`
- `project-docs/46-code-quality-enhancement-checklist.md`

## 当前结论
从代码侧看，项目已达到：
- 架构完整
- 模块完整
- mock → real 路线清晰
- 关键主线有自检能力

后续若进入搭建阶段，建议优先：
1. auth/rbac 真接入
2. question/search 真接入
3. user-center 真接入
