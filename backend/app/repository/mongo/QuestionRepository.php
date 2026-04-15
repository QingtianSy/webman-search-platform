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
        if (!MongoClient::isConfigured()) {
            return [];
        }

        /**
         * 未来真实查询示意：
         * db.questions.find({}, { question_id: 1, stem: 1, answer_text: 1, type_name: 1, source_name: 1, status: 1, created_at: 1 })
         */
        return [];
    }

    protected function saveAll(array $rows): void
    {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function findByQuestionId(int $questionId): array
    {
        foreach ($this->all() as $row) {
            if ((int) ($row['question_id'] ?? 0) === $questionId) {
                return $row;
            }
        }
        return [];
    }

    public function findList(array $filters = []): array
    {
        $rows = $this->all();
        $stem = trim((string) ($filters['stem'] ?? ''));
        if ($stem === '') {
            return $rows;
        }
        return array_values(array_filter($rows, function ($row) use ($stem) {
            return str_contains((string) ($row['stem'] ?? ''), $stem);
        }));
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
        if (!MongoClient::isConfigured()) {
            return [];
        }

        /**
         * 未来真实查询说明：
         * QuestionRepository 的 real 模式主要承载按 question_id 回查完整题目。
         * 真正全文搜索优先由 Elasticsearch 完成，命中后再回 Mongo 取完整文档。
         */
        return [];
    }

    public function update(int $questionId, array $data): array
    {
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

    public function delete(int $questionId): bool
    {
        $rows = array_values(array_filter($this->allMock(), fn ($row) => (int) ($row['question_id'] ?? 0) !== $questionId));
        $this->saveAll($rows);
        return true;
    }
}
