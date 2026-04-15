# backend

Webman 后端代码目录。

## 当前状态
当前代码已进入 **代码侧生产级自检可执行阶段**：
- 已有 auth/rbac 真接入脚本链
- 已有 question/search 真接入脚本链
- 已有 mock 模式下的 smoke tests
- 在不启动真实服务器的情况下，也可以先验证关键代码主线

## 当前可用自检方式
- `composer smoke`
- `scripts/run_backend_smoke_tests.sh`
