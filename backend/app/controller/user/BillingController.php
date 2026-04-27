<?php

namespace app\controller\user;

use app\exception\BusinessException;
use app\service\user\BillingService;
use support\ApiResponse;
use support\Db;
use support\Request;

class BillingController
{
    public function wallet(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new BillingService())->wallet($userId));
    }

    public function currentPlan(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        return ApiResponse::success((new BillingService())->currentPlan($userId));
    }

    // 套餐列表（公开给用户端展示）：仅 status=1；features 反序列化 JSON；
    // plan_type 过滤用 code 前缀约定：unlimited_ / limited_ / exhaustive_（如无前缀按 is_unlimited 推导）。
    // is_recommended 沿 features.is_recommended 读取，允许后台运营标记。
    public function planList(Request $request)
    {
        $type = (string) $request->get('plan_type', '');
        try {
            $query = Db::table('plans')->where('status', 1)->orderBy('sort')->orderBy('id');
            $rows = $query->get([
                'id', 'name', 'code', 'price', 'duration', 'quota',
                'is_unlimited', 'features', 'sort', 'status',
            ]);
        } catch (\Throwable $e) {
            error_log('[BillingController] planList failed: ' . $e->getMessage());
            throw new BusinessException('套餐服务暂不可用，请稍后重试', 50001);
        }

        $list = [];
        foreach ($rows as $r) {
            $row = (array) $r;
            $features = [];
            if (!empty($row['features'])) {
                $decoded = json_decode((string) $row['features'], true);
                if (is_array($decoded)) {
                    $features = $decoded;
                }
            }
            $planType = $this->inferPlanType((string) $row['code'], (int) $row['is_unlimited'], (int) $row['quota']);
            if ($type !== '' && $planType !== $type) {
                continue;
            }
            $list[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'plan_type' => $planType,
                'price' => (string) $row['price'],
                'duration' => (int) $row['duration'],
                'quota' => (int) $row['quota'],
                'is_unlimited' => (int) $row['is_unlimited'],
                'features' => $features,
                'is_recommended' => (int) ($features['is_recommended'] ?? 0),
                'sort' => (int) $row['sort'],
                'status' => (int) $row['status'],
            ];
        }
        return ApiResponse::success(['list' => $list]);
    }

    protected function inferPlanType(string $code, int $isUnlimited, int $quota): string
    {
        if (str_starts_with($code, 'unlimited_') || $isUnlimited === 1) {
            return 'unlimited';
        }
        if (str_starts_with($code, 'exhaustive_')) {
            return 'exhaustive';
        }
        if (str_starts_with($code, 'limited_')) {
            return 'limited';
        }
        // fallback：quota>0 归为 limited，否则 exhaustive（次卡/一次性）
        return $quota > 0 ? 'limited' : 'exhaustive';
    }
}
