<?php

namespace app\controller\user;

use support\ApiResponse;

class SearchController
{
    public function query(): array
    {
        return ApiResponse::success([
            'log_no' => 'TODO_LOG_NO',
            'hit_count' => 0,
            'consume_quota' => 0,
            'list' => [],
        ], '搜题接口骨架已创建');
    }

    public function logs(): array
    {
        return ApiResponse::success([
            'list' => [],
            'total' => 0,
            'page' => 1,
            'page_size' => 20,
        ]);
    }
}
