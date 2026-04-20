<?php

namespace app\service\question;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;
use support\adapter\MongoClient;

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

    public function rebuild(int $batchSize = 500): array
    {
        // 先验证数据源可用再动 ES：否则 Mongo 不可用时会把 ES 清空留下空库。
        if (!MongoClient::connection()) {
            return ['error' => 'MongoDB连接不可用，无法读取题目数据', 'rebuilt' => false];
        }
        $repo = new QuestionRepository();
        $total = $repo->countByFilters([]);

        $esRepo = new QuestionIndexRepository();
        if (!$esRepo->deleteIndex()) {
            return ['error' => 'ES索引删除失败，请检查ES连接配置', 'rebuilt' => false];
        }
        if (!$esRepo->createIndex()) {
            return ['error' => 'ES索引创建失败，请检查ES连接配置', 'rebuilt' => false];
        }
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

        $result = [
            'total' => $total,
            'indexed' => $indexed,
            'rebuilt' => true,
        ];
        if ($indexed < $total) {
            $failed = $total - $indexed;
            $result['es_warning'] = "部分文档索引失败：{$failed}/{$total} 条未成功写入ES";
        }
        return $result;
    }

    public function delete(string $questionId): bool
    {
        return (new QuestionIndexRepository())->deleteQuestion($questionId);
    }
}
