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
        // 强制 ping，绕过 MongoClient::connection() 的 30s 缓存窗口：
        // 缓存连接"可用"不代表此刻 Mongo 真的可读，破坏 ES 前必须拿实时信号。
        if (!MongoClient::ping()) {
            return ['error' => 'MongoDB连接不可用，无法读取题目数据', 'rebuilt' => false];
        }
        $repo = new QuestionRepository();
        try {
            // 严格 count：失败直接抛出，避免"读失败静默返回 0，ES 被清成空索引"。
            $total = $repo->countByFiltersStrict([]);
        } catch (\Throwable $e) {
            error_log("[QuestionIndexService] rebuild count failed: " . $e->getMessage());
            return ['error' => 'MongoDB 读取失败，已中止，未改动 ES', 'rebuilt' => false];
        }

        $esRepo = new QuestionIndexRepository();
        if (!$esRepo->deleteIndex()) {
            return ['error' => 'ES索引删除失败，请检查ES连接配置', 'rebuilt' => false];
        }
        if (!$esRepo->createIndex()) {
            return ['error' => 'ES索引创建失败，请检查ES连接配置', 'rebuilt' => false];
        }
        $indexed = 0;
        $page = 1;
        $readError = null;

        try {
            while (true) {
                // 严格分页：Mongo 中途掉线不再静默 break 循环伪装成成功。
                $batch = $repo->findPageStrict([], $page, $batchSize);
                if (empty($batch)) {
                    break;
                }
                $indexed += $esRepo->bulkIndex($batch);
                if (count($batch) < $batchSize) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
            $readError = $e->getMessage();
            error_log("[QuestionIndexService] rebuild read failed mid-loop: {$readError}");
        }

        $result = [
            'total' => $total,
            'indexed' => $indexed,
            'rebuilt' => true,
        ];
        if ($readError !== null) {
            $result['es_warning'] = "读取 MongoDB 中途失败，索引可能不完整: {$readError}";
        } elseif ($indexed < $total) {
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
