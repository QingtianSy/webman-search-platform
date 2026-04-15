<?php

namespace app\service\search;

class SearchService
{
    public function query(string $keyword, string $info = '', string $split = '###'): array
    {
        return [
            'log_no' => 'TODO_LOG_NO',
            'hit_count' => 0,
            'consume_quota' => 0,
            'list' => [],
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }
}
