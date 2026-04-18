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
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/questions.json';
    }

    protected function all(): array
    {
        return config('integration.question_source', 'mock') === 'real'
            ? $this->allReal()
            : $this->allMock();
    }

    protected function allMock(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }

    protected function allReal(): array
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
            error_log("[QuestionRepository] allReal failed: " . $e->getMessage());
            return [];
        }
    }

    protected function saveAll(array $rows): void
    {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function findByQuestionId(int $questionId): array
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->findByQuestionIdReal($questionId);
        }
        foreach ($this->allMock() as $row) {
            if ((int) ($row['question_id'] ?? 0) === $questionId) {
                return $row;
            }
        }
        return [];
    }

    protected function findByQuestionIdReal(int $questionId): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $doc = $db->selectCollection('questions')->findOne(['question_id' => $questionId]);
            return $doc ? $this->docToArray($doc) : [];
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionIdReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByQuestionIds(array $questionIds): array
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->findByQuestionIdsReal($questionIds);
        }
        $rows = $this->allMock();
        $idSet = array_flip($questionIds);
        return array_values(array_filter($rows, fn ($row) => isset($idSet[(int) ($row['question_id'] ?? 0)])));
    }

    protected function findByQuestionIdsReal(array $questionIds): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find([
                'question_id' => ['$in' => array_values(array_map('intval', $questionIds))],
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionIdsReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function findList(array $filters = []): array
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->findListReal($filters);
        }
        $rows = $this->allMock();
        $stem = trim((string) ($filters['stem'] ?? ''));
        if ($stem === '') {
            return $rows;
        }
        return array_values(array_filter($rows, function ($row) use ($stem) {
            return str_contains((string) ($row['stem'] ?? ''), $stem);
        }));
    }

    protected function findListReal(array $filters = []): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $query = [];
            $stem = trim((string) ($filters['stem'] ?? ''));
            if ($stem !== '') {
                $query['stem'] = ['$regex' => preg_quote($stem, '/'), '$options' => 'i'];
            }
            $cursor = $db->selectCollection('questions')->find($query, [
                'sort' => ['created_at' => -1],
                'limit' => 1000,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findListReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function search(string $keyword): array
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->searchReal($keyword);
        }
        return $this->searchMock($keyword);
    }

    protected function searchMock(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }
        $result = [];
        foreach ($this->allMock() as $row) {
            $haystacks = [
                (string) ($row['stem'] ?? ''),
                (string) ($row['answer_text'] ?? ''),
                implode(' ', array_map(fn ($item) => (string) ($item['text'] ?? ''), $row['options'] ?? [])),
                implode(' ', $row['keywords'] ?? []),
            ];
            foreach ($haystacks as $text) {
                if ($text !== '' && str_contains($text, $keyword)) {
                    $row['score'] = 100;
                    $result[] = $row;
                    break;
                }
            }
        }
        return $result;
    }

    protected function searchReal(string $keyword): array
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
            error_log("[QuestionRepository] searchReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function update(int $questionId, array $data): array
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->updateReal($questionId, $data);
        }
        $rows = $this->allMock();
        foreach ($rows as &$row) {
            if ((int) ($row['question_id'] ?? 0) === $questionId) {
                $row = array_merge($row, $data);
                $this->saveAll($rows);
                return $row;
            }
        }
        return [];
    }

    protected function updateReal(int $questionId, array $data): array
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
            return $this->findByQuestionIdReal($questionId);
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] updateReal failed: " . $e->getMessage());
            return [];
        }
    }

    public function delete(int $questionId): bool
    {
        if (config('integration.question_source', 'mock') === 'real') {
            return $this->deleteReal($questionId);
        }
        $rows = array_values(array_filter($this->allMock(), fn ($row) => (int) ($row['question_id'] ?? 0) !== $questionId));
        $this->saveAll($rows);
        return true;
    }

    protected function deleteReal(int $questionId): bool
    {
        $db = MongoClient::connection();
        if (!$db) {
            return false;
        }
        try {
            $result = $db->selectCollection('questions')->deleteOne(['question_id' => $questionId]);
            return $result->getDeletedCount() > 0;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] deleteReal failed: " . $e->getMessage());
            return false;
        }
    }

    public function importFromJsonl(string $filePath, string $taskNo): int
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
        $batch = [];
        $batchSize = 100;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $item = json_decode($line, true);
            if (!$item || empty($item['question'])) {
                continue;
            }

            $doc = $this->buildDocFromRaw($item, $taskNo, $now);
            $batch[] = $doc;

            if (count($batch) >= $batchSize) {
                $imported += $this->insertBatchDedup($collection, $batch);
                $batch = [];
            }
        }
        fclose($handle);

        if (!empty($batch)) {
            $imported += $this->insertBatchDedup($collection, $batch);
        }

        return $imported;
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

    protected function insertBatchDedup($collection, array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }
        try {
            $result = $collection->insertMany($batch, ['ordered' => false]);
            return $result->getInsertedCount();
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $writeResult = $e->getWriteResult();
            return $writeResult->getInsertedCount();
        }
    }

    protected function generateQuestionId(): string
    {
        return 'Q' . date('YmdHis') . bin2hex(random_bytes(4));
    }

    public function findByTaskNo(string $taskNo): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find(
                ['task_no' => $taskNo],
                ['sort' => ['created_at' => -1]]
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
