<?php

namespace app\service\question;

use app\repository\mongo\QuestionRepository;
use support\Pagination;

class QuestionService
{
    public function getList(array $filters = []): array
    {
        $repository = new QuestionRepository();
        $list = $repository->findList($filters);
        return Pagination::format($list, count($list), 1, 20);
    }

    public function detail(int $questionId): array
    {
        return (new QuestionRepository())->findByQuestionId($questionId);
    }
}
