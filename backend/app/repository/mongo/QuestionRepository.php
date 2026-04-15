<?php

namespace app\repository\mongo;

class QuestionRepository
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 3) . '/storage/mock/questions.json';
    }

    protected function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
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
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }
        $result = [];
        foreach ($this->all() as $row) {
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
}
