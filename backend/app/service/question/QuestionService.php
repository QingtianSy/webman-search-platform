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

    public function findManyByIds(array $ids): array
    {
        $list = [];
        foreach ($ids as $id) {
            $row = (new QuestionRepository())->findByQuestionId((int) $id);
            if ($row) {
                $list[] = $row;
            }
        }
        return $list;
    }
}
