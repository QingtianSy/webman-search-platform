# User-Center 真实替换方案

## 范围
- wallets
- user_subscriptions
- user_api_keys
- announcements

## 当前对应类
- `WalletRepository`
- `SubscriptionRepository`
- `ApiKeyRepository`
- `AnnouncementRepository`
- `ApiKeyService`
- `DashboardService`

## 替换顺序
1. 建表：`0003_user_center_schema.sql`
2. 先替换 Repository real 分支
3. 再验证：
   - `/api/v1/user/wallet/detail`
   - `/api/v1/user/plan/current`
   - `/api/v1/user/api-key/list`
   - `/api/v1/user/dashboard/overview`
4. 最后再做 create / toggle / delete 的真实持久化

## 注意事项
- API Key 不再存明文 secret，只保留创建时明文返回一次
- 公告面向全体用户，建议单独列表缓存
- dashboard 由多个 Repository 聚合返回
