<?php

namespace app\repository\mongo;

use support\adapter\MongoClient;

class QuestionRepository
{
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
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
