<?php

namespace app\repository\es;

use GuzzleHttp\Client;
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

        $client = new Client([
            'base_uri' => ElasticsearchClient::baseUri(),
            'verify' => ElasticsearchClient::sslOptions()['verify'],
            'auth' => [
                ElasticsearchClient::auth()['username'],
                ElasticsearchClient::auth()['password'],
            ],
            'timeout' => 10,
        ]);

        $response = $client->post('/' . ElasticsearchClient::questionIndex() . '/_search', [
            'json' => [
                'query' => [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => ['stem^3', 'options_text^2', 'answer_text', 'analysis'],
                    ],
                ],
                'size' => 20,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $hits = $data['hits']['hits'] ?? [];

        return array_map(function ($row) {
            $source = $row['_source'] ?? [];
            return [
                'question_id' => $source['question_id'] ?? null,
                'score' => $row['_score'] ?? null,
            ];
        }, $hits);
    }
}
