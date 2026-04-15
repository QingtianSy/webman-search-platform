# Code-side self-check / smoke tests

## 目的
在不启动真实服务器、不接真实环境的前提下，先验证关键代码主线在 mock 模式下可用。

## 当前脚本
- `scripts/run_backend_smoke_tests.sh`
- `composer smoke`（在 backend 目录中执行）

## 当前覆盖
- auth mock smoke
- search mock smoke
- dashboard mock smoke

## 后续扩展建议
- question detail smoke
- api key list smoke
- doc config smoke
- collect task detail smoke
- auth/rbac real smoke（待真实数据库接入后）
