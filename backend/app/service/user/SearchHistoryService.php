<?php

namespace app\service\user;

use app\common\user\UserListBuilder;

class SearchHistoryService
{
    public function getList(int $userId, array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;

        // 当前阶段先保留最小实现，后续接 search_logs / search_log_details 真查询。
        return UserListBuilder::make([], $page, $pageSize);
    }
}
