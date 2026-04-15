<?php

namespace app\repository\mongo;

class QuestionRepository
{
    public function findByQuestionId(int $questionId): array
    {
        return [];
    }

    public function findList(array $filters = []): array
    {
        return [
            [
                'question_id' => 100001,
                'stem' => $filters['stem'] ?? '示例题目',
                'answer_text' => 'A',
                'type_name' => '单选题',
                'source_name' => '本地题库',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }
}
