# User-Center 真实接入脚本说明

## 当前可用脚本
- `scripts/apply_user_center_schema.sh`

## 推荐顺序
1. 先完成 auth/rbac 真接入
2. 执行 `apply_user_center_schema.sh`
3. 替换 Wallet / Subscription / ApiKey / Announcement Repository 的 real 分支
4. 验证用户中心接口

## 优先验证接口
- `/api/v1/user/dashboard/overview`
- `/api/v1/user/wallet/detail`
- `/api/v1/user/plan/current`
- `/api/v1/user/api-key/list`
