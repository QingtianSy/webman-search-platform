<?php

namespace app\service\question;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;

class QuestionIndexService
{
    public function sync(string $questionId): bool
    {
        $question = (new QuestionRepository())->findByQuestionId($questionId);
        if (empty($question)) {
            return false;
        }
        return (new QuestionIndexRepository())->indexQuestion($question);
    }

    public function syncAll(int $batchSize = 500): array
    {
        $repo = new QuestionRepository();
        $esRepo = new QuestionIndexRepository();

        $total = $repo->countByFilters([]);
        $indexed = 0;
        $page = 1;

        while (true) {
            $batch = $repo->findPage([], $page, $batchSize);
            if (empty($batch)) {
                break;
            }
            $indexed += $esRepo->bulkIndex($batch);
            if (count($batch) < $batchSize) {
                break;
            }
            $page++;
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

    public function delete(string $questionId): bool
    {
        return (new QuestionIndexRepository())->deleteQuestion($questionId);
    }
}
