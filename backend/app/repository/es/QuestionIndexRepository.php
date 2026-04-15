<?php

namespace app\repository\es;

use support\adapter\ElasticsearchClient;

class QuestionIndexRepository
{
    public function search(string $keyword): array
    {
        return config('integration.question_source', 'mock') === 'real'
            ? $this->searchReal($keyword)
            : $this->searchMock($keyword);
    }

    protected function searchMock(string $keyword): array
    {
        return [];
    }

    protected function searchReal(string $keyword): array
    {
        if (!ElasticsearchClient::isConfigured() || trim($keyword) === '') {
            return [];
        }

        /**
         * 未来真实查询示意：
         * POST question_index/_search
         * {
         *   "query": {
         *     "multi_match": {
         *       "query": "keyword",
         *       "fields": ["stem^3", "options_text^2", "answer_text", "analysis"]
         *     }
         *   }
         * }
         */
        return [];
    }
}
