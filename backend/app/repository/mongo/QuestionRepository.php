<?php

namespace app\repository\mongo;

use support\adapter\MongoClient;

class QuestionRepository
{
    protected const TYPE_MAP = [
        '单选题' => ['code' => 'single',     'name' => '单选题'],
        '多选题' => ['code' => 'multiple',   'name' => '多选题'],
        '判断题' => ['code' => 'judgement',   'name' => '判断题'],
        '填空题' => ['code' => 'completion', 'name' => '填空题'],
    ];

    protected function all(): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find([], [
                'sort' => ['created_at' => -1],
                'limit' => 1000,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByQuestionId(string $questionId): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $doc = $db->selectCollection('questions')->findOne(['question_id' => $questionId]);
            return $doc ? $this->docToArray($doc) : [];
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionId failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByQuestionIds(array $questionIds): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find([
                'question_id' => ['$in' => array_values($questionIds)],
            ]);
            $map = [];
            foreach ($cursor as $doc) {
                $row = $this->docToArray($doc);
                $map[$row['question_id'] ?? ''] = $row;
            }
            $ordered = [];
            foreach ($questionIds as $id) {
                if (isset($map[$id])) {
                    $ordered[] = $map[$id];
                }
            }
            return $ordered;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionIds failed: " . $e->getMessage());
            return [];
        }
    }

    public function countByFilters(array $filters = []): int
    {
        $db = MongoClient::connection();
        if (!$db) {
            return 0;
        }
        try {
            $query = self::buildQuery($filters);
            return $db->selectCollection('questions')->countDocuments($query);
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] countByFilters failed: " . $e->getMessage());
            return 0;
        }
    }

    // ES 重建等"破坏性写入前必须确认数据源可读"的场景使用：连接失败/查询异常统一抛出，
    // 避免在 rebuild 途中 Mongo 掉线时静默返回 0/[] 把 ES 清成空索引。
    public function countByFiltersStrict(array $filters = []): int
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $query = self::buildQuery($filters);
        return $db->selectCollection('questions')->countDocuments($query);
    }

    public function findPage(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $query = self::buildQuery($filters);
            $cursor = $db->selectCollection('questions')->find($query, [
                'sort' => ['created_at' => -1],
                'skip' => ($page - 1) * $pageSize,
                'limit' => $pageSize,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格分页：同 countByFiltersStrict，用于 rebuild 中途 Mongo 掉线时让调用方感知（而非静默返回 []）。
    public function findPageStrict(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $query = self::buildQuery($filters);
        $cursor = $db->selectCollection('questions')->find($query, [
            'sort' => ['created_at' => -1],
            'skip' => ($page - 1) * $pageSize,
            'limit' => $pageSize,
        ]);
        $rows = [];
        foreach ($cursor as $doc) {
            $rows[] = $this->docToArray($doc);
        }
        return $rows;
    }

    public function findList(array $filters = [], int $limit = 1000): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $query = self::buildQuery($filters);
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find($query, $options);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findList failed: " . $e->getMessage());
            return [];
        }
    }

    public function findListIterator(array $filters = [], int $limit = 0): \Generator
    {
        $db = MongoClient::connection();
        if (!$db) {
            return;
        }
        try {
            $query = self::buildQuery($filters);
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find($query, $options);
            foreach ($cursor as $doc) {
                yield $this->docToArray($doc);
            }
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findListIterator failed: " . $e->getMessage());
        }
    }

    public function search(string $keyword): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $escapedKeyword = preg_quote(trim($keyword), '/');
            $cursor = $db->selectCollection('questions')->find([
                '$or' => [
                    ['stem' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                    ['answer_text' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                    ['keywords' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                ],
            ], [
                'limit' => 100,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $row = $this->docToArray($doc);
                $row['score'] = 100;
                $rows[] = $row;
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] search failed: " . $e->getMessage());
            return [];
        }
    }

    public function update(string $questionId, array $data): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $db->selectCollection('questions')->updateOne(
                ['question_id' => $questionId],
                ['$set' => $data]
            );
            return $this->findByQuestionId($questionId);
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] update failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(string $questionId): bool
    {
        $db = MongoClient::connection();
        if (!$db) {
            return false;
        }
        try {
            $result = $db->selectCollection('questions')->deleteOne(['question_id' => $questionId]);
            return $result->getDeletedCount() > 0;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    public function importFromJsonl(string $filePath, string $taskNo): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $collection = $db->selectCollection('questions');
        $now = date('Y-m-d H:i:s');
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $failedReasons = [];
        $batch = [];
        $batchSize = 100;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $item = json_decode($line, true);
            if (!$item || empty($item['question'])) {
                $skipped++;
                continue;
            }

            $doc = $this->buildDocFromRaw($item, $taskNo, $now);
            $batch[] = $doc;

            if (count($batch) >= $batchSize) {
                $res = $this->insertBatchDedup($collection, $batch);
                $imported += $res['inserted'];
                $failed += $res['failed'];
                foreach ($res['reasons'] as $reason) {
                    if (count($failedReasons) < 20) {
                        $failedReasons[] = $reason;
                    }
                }
                $batch = [];
            }
        }
        fclose($handle);

        if (!empty($batch)) {
            $res = $this->insertBatchDedup($collection, $batch);
            $imported += $res['inserted'];
            $failed += $res['failed'];
            foreach ($res['reasons'] as $reason) {
                if (count($failedReasons) < 20) {
                    $failedReasons[] = $reason;
                }
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'failed_reasons' => $failedReasons,
        ];
    }

    protected function buildDocFromRaw(array $item, string $taskNo, string $now): array
    {
        $quetype = $item['quetype'] ?? '';
        $typeInfo = self::TYPE_MAP[$quetype] ?? ['code' => 'single', 'name' => $quetype];

        $question = trim($item['question'] ?? '');
        $optionsRaw = trim($item['options'] ?? '');
        $answer = trim($item['answer'] ?? '');

        $md5 = md5($question . $optionsRaw . $answer);
        $options = $this->parseOptions($optionsRaw);

        return [
            'question_id' => $this->generateQuestionId(),
            'md5' => $md5,
            'type_code' => $typeInfo['code'],
            'type_name' => $typeInfo['name'],
            'source_name' => '系统采集',
            'course_name' => $item['course_name'] ?? '',
            'course_id' => $item['course_id'] ?? '',
            'task_no' => $taskNo,
            'stem' => $question,
            'stem_plain' => $question,
            'options' => $options,
            'options_text' => $optionsRaw,
            'answer_text' => $answer,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function parseOptions(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/\s*\|\s*/', $raw);
        $options = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^([A-Z])\.(.+)$/', $part, $m)) {
                $options[] = ['label' => $m[1], 'content' => trim($m[2])];
            } else {
                $options[] = ['label' => '', 'content' => $part];
            }
        }
        return $options;
    }

    protected function insertBatchDedup($collection, array $batch): array
    {
        if (empty($batch)) {
            return ['inserted' => 0, 'failed' => 0, 'reasons' => []];
        }
        try {
            $result = $collection->insertMany($batch, ['ordered' => false]);
            return ['inserted' => $result->getInsertedCount(), 'failed' => 0, 'reasons' => []];
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $writeResult = $e->getWriteResult();
            $inserted = $writeResult->getInsertedCount();
            $writeErrors = $writeResult->getWriteErrors();
            $reasons = [];
            foreach ($writeErrors as $err) {
                $reasons[] = $err->getMessage();
            }
            return ['inserted' => $inserted, 'failed' => count($batch) - $inserted, 'reasons' => $reasons];
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] insertBatchDedup failed: " . $e->getMessage());
            return ['inserted' => 0, 'failed' => count($batch), 'reasons' => [$e->getMessage()]];
        }
    }

    protected function generateQuestionId(): string
    {
        return 'Q' . date('YmdHis') . bin2hex(random_bytes(4));
    }

    public function findByTaskNo(string $taskNo, int $limit = 0): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find(
                ['task_no' => $taskNo],
                $options
            );
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByTaskNo failed: " . $e->getMessage());
            return [];
        }
    }

    protected static function buildQuery(array $filters): array
    {
        $query = [];
        $stem = trim((string) ($filters['stem'] ?? ''));
        if ($stem !== '') {
            $query['stem'] = ['$regex' => preg_quote($stem, '/'), '$options' => 'i'];
        }
        return $query;
    }

    protected function docToArray($doc): array
    {
        if ($doc instanceof \MongoDB\Model\BSONDocument) {
            $arr = (array) $doc->getArrayCopy();
            if (isset($arr['_id'])) {
                $arr['_id'] = (string) $arr['_id'];
            }
            foreach ($arr as $k => $v) {
                if ($v instanceof \MongoDB\Model\BSONArray) {
                    $arr[$k] = $v->getArrayCopy();
                } elseif ($v instanceof \MongoDB\Model\BSONDocument) {
                    $arr[$k] = (array) $v->getArrayCopy();
                }
            }
            return $arr;
        }
        return is_array($doc) ? $doc : (array) $doc;
    }
}
