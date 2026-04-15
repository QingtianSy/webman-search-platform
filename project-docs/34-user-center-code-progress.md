# User-Center 代码推进说明

## 本轮新增的代码层改进
1. `WalletRepository` 已支持 mock / real 双模式
2. `SubscriptionRepository` 已支持 mock / real 双模式
3. `ApiKeyRepository` 已支持 mock / real 双模式
4. `AnnouncementRepository` 已支持 mock / real 双模式

## 当前真实查询目标
### wallets
- 用户余额
- 冻结余额
- 充值/消费累计

### user_subscriptions
- 当前套餐
- 剩余额度
- 已用额度
- 到期时间

### user_api_keys
- API Key 列表
- API Key 详情
- 删除

### announcements
- 公告列表
- 创建/更新

## 下一步建议
1. 让 DashboardService 逐步依赖真实 Repository
2. 切换 `AUTH_RBAC_SOURCE=real` 后继续验证用户中心读接口
3. 再补 user_api_keys 创建/toggle 的真实持久化
