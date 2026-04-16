<?php

namespace app\repository\es;

use GuzzleHttp\Client;
use Throwable;
use support\adapter\ElasticsearchClient;

class QuestionIndexRepository
{
    public function search(string $keyword): array
    {
        $source = env('QUESTION_SOURCE', 'mock');

        if ($source !== 'real') {
            $path = dirname(__DIR__, 2) . '/config/integration.php';
            $all = is_file($path) ? require $path : [];
            $source = $all['question_source'] ?? 'mock';
        }

        return $source === 'real'
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

        try {
            $client = new Client([
                'base_uri' => ElasticsearchClient::host(),
                'verify' => false,
                'auth' => [
                    ElasticsearchClient::username(),
                    ElasticsearchClient::password(),
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
        } catch (Throwable $e) {
            @file_put_contents(
                base_path() . '/runtime/logs/es_search_error.log',
                date('c') . ' ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
            return [];
        }
    }
}
