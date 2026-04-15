<?php

namespace app\service\search;

use app\repository\mongo\QuestionRepository;
use app\service\log\LogService;

class SearchService
{
    public function query(string $keyword, string $info = '', string $split = '###'): array
    {
        $logService = new LogService();
        $logService->info('search.query', [
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ]);

        $repository = new QuestionRepository();
        $list = $repository->search($keyword);
        $hitCount = count($list);

        return [
            'log_no' => 'SL' . date('YmdHis'),
            'hit_count' => $hitCount,
            'consume_quota' => $hitCount > 0 ? 1 : 0,
            'list' => $list,
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }
}
