<?php

namespace app\service\search;

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

        $logNo = 'SL' . date('YmdHis');
        return [
            'log_no' => $logNo,
            'hit_count' => 1,
            'consume_quota' => 1,
            'list' => [
                [
                    'question_id' => 100001,
                    'stem' => $keyword,
                    'options' => [],
                    'answers' => ['A'],
                    'answer_text' => 'TODO_ANSWER',
                    'analysis' => 'TODO_ANALYSIS',
                    'type_name' => '单选题',
                    'source_name' => '本地题库',
                    'score' => 100,
                ],
            ],
            'keyword' => $keyword,
            'info' => $info,
            'split' => $split,
        ];
    }
}
