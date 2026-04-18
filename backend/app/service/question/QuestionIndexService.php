<?php

namespace app\service\question;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;

class QuestionIndexService
{
    public function sync(int $questionId): bool
    {
        $question = (new QuestionRepository())->findByQuestionId($questionId);
        if (empty($question)) {
            return false;
        }
        return (new QuestionIndexRepository())->indexQuestion($question);
    }

    public function syncAll(int $batchSize = 100): array
    {
        $repo = new QuestionRepository();
        $esRepo = new QuestionIndexRepository();

        $all = $repo->findList();
        $total = count($all);
        $indexed = 0;

        foreach (array_chunk($all, $batchSize) as $batch) {
            $indexed += $esRepo->bulkIndex($batch);
        }

        return [
            'total' => $total,
            'indexed' => $indexed,
        ];
    }

    public function createIndex(): bool
    {
        return (new QuestionIndexRepository())->createIndex();
    }

    public function delete(int $questionId): bool
    {
        return (new QuestionIndexRepository())->deleteQuestion($questionId);
    }
}
