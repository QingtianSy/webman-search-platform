<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\SubscriptionRepository;

class PlanAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = [];
        $current = (new SubscriptionRepository())->findCurrentByUserId(1);
        if (!empty($current)) {
            $list[] = $current;
        }
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword);
            }));
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }
}
