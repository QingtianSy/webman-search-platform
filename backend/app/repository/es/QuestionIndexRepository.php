<?php

namespace app\repository\es;

use GuzzleHttp\Client;
use Throwable;
use support\adapter\ElasticsearchClient;

class QuestionIndexRepository
{
    protected static ?Client $client = null;

    public function search(string $keyword): array
    {
        if (!ElasticsearchClient::isConfigured() || trim($keyword) === '') {
            return [];
        }

        try {
            $client = $this->client();
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
            error_log("[QuestionIndexRepository] search failed: " . $e->getMessage());
            return [];
        }
    }

    public function indexQuestion(array $question): bool
    {
        if (!ElasticsearchClient::isConfigured()) {
            return false;
        }
        try {
            $client = $this->client();
            $questionId = $question['question_id'] ?? 0;
            $body = $this->buildIndexBody($question);
            $client->put('/' . ElasticsearchClient::questionIndex() . '/_doc/' . $questionId, [
                'json' => $body,
            ]);
            return true;
        } catch (Throwable $e) {
            error_log("[QuestionIndexRepository] indexQuestion failed: " . $e->getMessage());
            return false;
        }
    }

    public function bulkIndex(array $questions): int
    {
        if (!ElasticsearchClient::isConfigured() || empty($questions)) {
            return 0;
        }
        try {
            $client = $this->client();
            $index = ElasticsearchClient::questionIndex();
            $ndjson = '';
            foreach ($questions as $q) {
                $id = $q['question_id'] ?? 0;
                $ndjson .= json_encode(['index' => ['_index' => $index, '_id' => $id]]) . "\n";
                $ndjson .= json_encode($this->buildIndexBody($q)) . "\n";
            }
            $response = $client->post('/_bulk', [
                'headers' => ['Content-Type' => 'application/x-ndjson'],
                'body' => $ndjson,
            ]);
            $result = json_decode((string) $response->getBody(), true);
            if (!empty($result['errors'])) {
                $errorCount = 0;
                foreach ($result['items'] ?? [] as $item) {
                    $action = $item['index'] ?? $item['create'] ?? [];
                    if (isset($action['error'])) {
                        $errorCount++;
                    }
                }
                error_log("[QuestionIndexRepository] bulkIndex had {$errorCount} errors out of " . count($questions));
                return count($questions) - $errorCount;
            }
            return count($questions);
        } catch (Throwable $e) {
            error_log("[QuestionIndexRepository] bulkIndex failed: " . $e->getMessage());
            return 0;
        }
    }

    public function createIndex(): bool
    {
        if (!ElasticsearchClient::isConfigured()) {
            return false;
        }
        try {
            $client = $this->client();
            $index = ElasticsearchClient::questionIndex();
            $client->put('/' . $index, [
                'json' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'analysis' => [
                            'analyzer' => [
                                'ik_smart_analyzer' => [
                                    'type' => 'custom',
                                    'tokenizer' => 'ik_smart',
                                ],
                            ],
                        ],
                    ],
                    'mappings' => [
                        'properties' => [
                            'question_id' => ['type' => 'keyword'],
                            'stem' => ['type' => 'text', 'analyzer' => 'ik_smart'],
                            'options_text' => ['type' => 'text', 'analyzer' => 'ik_smart'],
                            'answer_text' => ['type' => 'text', 'analyzer' => 'ik_smart'],
                            'analysis' => ['type' => 'text', 'analyzer' => 'ik_smart'],
                            'type_name' => ['type' => 'keyword'],
                            'source_name' => ['type' => 'keyword'],
                            'category_name' => ['type' => 'keyword'],
                            'keywords' => ['type' => 'keyword'],
                            'status' => ['type' => 'integer'],
                            'created_at' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss||epoch_millis'],
                        ],
                    ],
                ],
            ]);
            return true;
        } catch (Throwable $e) {
            error_log("[QuestionIndexRepository] createIndex failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteQuestion(string $questionId): bool
    {
        if (!ElasticsearchClient::isConfigured()) {
            return false;
        }
        try {
            $client = $this->client();
            $client->delete('/' . ElasticsearchClient::questionIndex() . '/_doc/' . $questionId);
            return true;
        } catch (Throwable $e) {
            error_log("[QuestionIndexRepository] deleteQuestion failed: " . $e->getMessage());
            return false;
        }
    }

    protected function buildIndexBody(array $q): array
    {
        $optionsText = '';
        if (!empty($q['options']) && is_array($q['options'])) {
            $optionsText = implode(' ', array_map(fn ($o) => (string) ($o['content'] ?? ''), $q['options']));
        }
        return [
            'question_id' => $q['question_id'] ?? '',
            'stem' => $q['stem'] ?? '',
            'options_text' => $optionsText,
            'answer_text' => $q['answer_text'] ?? '',
            'analysis' => $q['analysis'] ?? '',
            'type_name' => $q['type_name'] ?? '',
            'source_name' => $q['source_name'] ?? '',
            'category_name' => $q['category_name'] ?? '',
            'keywords' => $q['keywords'] ?? [],
            'status' => $q['status'] ?? 1,
            'created_at' => $q['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    protected function client(): Client
    {
        if (self::$client !== null) {
            return self::$client;
        }
        self::$client = new Client([
            'base_uri' => ElasticsearchClient::host(),
            'verify' => false,
            'auth' => [
                ElasticsearchClient::username(),
                ElasticsearchClient::password(),
            ],
            'timeout' => 30,
        ]);
        return self::$client;
    }
}
