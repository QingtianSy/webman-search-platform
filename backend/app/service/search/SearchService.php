<?php

namespace app\service\search;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;
use app\service\log\LogService;
use app\service\question\QuestionService;

class SearchService
{
    public function query(string $keyword, string $info = '', string $split = '###'): array
    {
        return config('integration.question_source', 'mock') === 'real'
            ? $this->queryReal($keyword, $info, $split)
            : $this->queryMock($keyword, $info, $split);
    }

    protected function queryMock(string $keyword, string $info = '', string $split = '###'): array
    {
        $logService = new LogService();
        $logService->info('search.query.mock', [
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
            'mode' => 'mock',
        ];
    }

    protected function queryReal(string $keyword, string $info = '', string $split = '###'): array
    {
        $logService = new LogService();
        $logService->info('search.query.real', [
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ]);

        $esHits = (new QuestionIndexRepository())->search($keyword);

        /**
         * 未来真实流程：
         * 1. ES 返回命中的 question_id 列表
         * 2. QuestionService 逐个/批量回 Mongo 取完整题目
         */
        $questionIds = array_map(fn ($row) => (int) ($row['question_id'] ?? 0), $esHits);
        $questionIds = array_values(array_filter($questionIds));
        $list = (new QuestionService())->findManyByIds($questionIds);

        return [
            'log_no' => 'SL' . date('YmdHis'),
            'hit_count' => count($list),
            'consume_quota' => count($list) > 0 ? 1 : 0,
            'list' => $list,
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
            'mode' => 'real',
        ];
    }
}
