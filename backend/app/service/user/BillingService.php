<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\WalletRepository;

class BillingService
{
    // 钱包/订阅两个读接口是计费页直接依赖的数据。之前 DB 故障静默返 []，页面显示"0 余额 / 无订阅"，
    // 会诱导用户退订/重新冲值。改为 DB 故障时抛 50001，让前端显示错误横幅而非伪造数据。
    // 用户真没钱包/没订阅（空结果）仍然返回 []，由前端显示"未开通"/"未设置"——这才是真实业务状态。

    public function wallet(int $userId): array
    {
        try {
            return (new WalletRepository())->findByUserIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('钱包服务暂不可用，请稍后重试', 50001);
        }
    }

    public function currentPlan(int $userId): array
    {
        try {
            return (new SubscriptionRepository())->findCurrentByUserIdStrict($userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('订阅服务暂不可用，请稍后重试', 50001);
        }
    }
}
