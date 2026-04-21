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

    public function detail(string $questionId): array
    {
        return (new QuestionRepository())->findByQuestionId($questionId);
    }

    public function findManyByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return (new QuestionRepository())->findByQuestionIds($ids);
    }

    // 搜索主链路用：ES 命中后回 Mongo 取详情时，Mongo 故障不再静默返空。
    public function findManyByIdsStrict(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return (new QuestionRepository())->findByQuestionIdsStrict($ids);
    }

    // 搜索主链路用：Mongo 正则兜底，连接失败/异常抛出，让上层感知"数据源不可用"而不是"没搜到"。
    public function searchMongoStrict(string $keyword): array
    {
        return (new QuestionRepository())->searchStrict($keyword);
    }
}
