<?php

namespace app\controller\admin;

use support\ApiResponse;
use support\Pagination;

class SearchLogController
{
    public function index(): array
    {
        $file = dirname(__DIR__, 3) . '/storage/logs/search_logs.jsonl';
        $list = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $list[] = $decoded;
                }
            }
        }
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
