# backend tests

当前目录用于放置**代码侧冒烟测试**。

## 当前脚本
- `auth_mock_smoke.php`
- `search_mock_smoke.php`
- `dashboard_mock_smoke.php`

## 当前目标
- 不依赖服务器部署
- 不依赖真实 MySQL / MongoDB / ES / Redis
- 验证 mock 模式下关键主线是否保持可用

## 后续建议
- 增加 auth/rbac real 模式测试
- 增加 search real 模式测试
- 增加 API Key / wallet / doc / logs 的 smoke tests
