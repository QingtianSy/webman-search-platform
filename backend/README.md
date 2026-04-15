# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **代码侧健全性增强阶段**：
- 已有 auth/rbac 真接入脚本链
- 已有 question/search 真接入脚本链
- 已有 mock 模式下更完整的 smoke tests
- 即使暂不部署，也能对关键主线做基础自检

## 当前建议
- 后续如果继续不部署，优先扩展 smoke tests 和关键真实分支
- 一旦开始搭建，优先跑 auth/rbac -> question/search -> user-center
