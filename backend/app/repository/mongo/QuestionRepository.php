<?php

namespace app\repository\mongo;

use support\adapter\MongoClient;

class QuestionRepository
{
    protected const TYPE_MAP = [
        '单选题' => ['code' => 'single',     'name' => '单选题'],
        '多选题' => ['code' => 'multiple',   'name' => '多选题'],
        '判断题' => ['code' => 'judgement',   'name' => '判断题'],
        '填空题' => ['code' => 'completion', 'name' => '填空题'],
    ];

    protected function all(): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find([], [
                'sort' => ['created_at' => -1],
                'limit' => 1000,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function findByQuestionId(string $questionId): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $doc = $db->selectCollection('questions')->findOne(['question_id' => $questionId]);
            return $doc ? $this->docToArray($doc) : [];
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionId failed: " . $e->getMessage());
            return [];
        }
    }

    // 管理端 detail 用：连接失败/查询异常抛出而非静默返回 []，
    // 避免 Mongo 掉线时管理员看到 40004"题目不存在"这种误导性提示。
    public function findByQuestionIdStrict(string $questionId): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $doc = $db->selectCollection('questions')->findOne(['question_id' => $questionId]);
        return $doc ? $this->docToArray($doc) : [];
    }

    public function findByQuestionIds(array $questionIds): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->find([
                'question_id' => ['$in' => array_values($questionIds)],
            ]);
            $map = [];
            foreach ($cursor as $doc) {
                $row = $this->docToArray($doc);
                $map[$row['question_id'] ?? ''] = $row;
            }
            $ordered = [];
            foreach ($questionIds as $id) {
                if (isset($map[$id])) {
                    $ordered[] = $map[$id];
                }
            }
            return $ordered;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByQuestionIds failed: " . $e->getMessage());
            return [];
        }
    }

    // 搜索主链路用：ES 已命中后回 Mongo 取详情，连接/查询异常直接抛出。
    // 避免 Mongo 掉线时把"有命中但取不到详情"伪装成"没搜到"。
    public function findByQuestionIdsStrict(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $cursor = $db->selectCollection('questions')->find([
            'question_id' => ['$in' => array_values($questionIds)],
        ]);
        $map = [];
        foreach ($cursor as $doc) {
            $row = $this->docToArray($doc);
            $map[$row['question_id'] ?? ''] = $row;
        }
        $ordered = [];
        foreach ($questionIds as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }
        return $ordered;
    }

    public function countByFilters(array $filters = []): int
    {
        $db = MongoClient::connection();
        if (!$db) {
            return 0;
        }
        try {
            $query = self::buildQuery($filters);
            return $db->selectCollection('questions')->countDocuments($query);
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] countByFilters failed: " . $e->getMessage());
            return 0;
        }
    }

    // ES 重建等"破坏性写入前必须确认数据源可读"的场景使用：连接失败/查询异常统一抛出，
    // 避免在 rebuild 途中 Mongo 掉线时静默返回 0/[] 把 ES 清成空索引。
    public function countByFiltersStrict(array $filters = []): int
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $query = self::buildQuery($filters);
        return $db->selectCollection('questions')->countDocuments($query);
    }

    public function findPage(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $query = self::buildQuery($filters);
            $cursor = $db->selectCollection('questions')->find($query, [
                'sort' => ['created_at' => -1],
                'skip' => ($page - 1) * $pageSize,
                'limit' => $pageSize,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格分页：同 countByFiltersStrict，用于 rebuild 中途 Mongo 掉线时让调用方感知（而非静默返回 []）。
    public function findPageStrict(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $query = self::buildQuery($filters);
        $cursor = $db->selectCollection('questions')->find($query, [
            'sort' => ['created_at' => -1],
            'skip' => ($page - 1) * $pageSize,
            'limit' => $pageSize,
        ]);
        $rows = [];
        foreach ($cursor as $doc) {
            $rows[] = $this->docToArray($doc);
        }
        return $rows;
    }

    public function findList(array $filters = [], int $limit = 1000): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $query = self::buildQuery($filters);
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find($query, $options);
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findList failed: " . $e->getMessage());
            return [];
        }
    }

    public function findListIterator(array $filters = [], int $limit = 0): \Generator
    {
        $db = MongoClient::connection();
        if (!$db) {
            return;
        }
        try {
            $query = self::buildQuery($filters);
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find($query, $options);
            foreach ($cursor as $doc) {
                yield $this->docToArray($doc);
            }
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findListIterator failed: " . $e->getMessage());
        }
    }

    // 管理端 export 用：连接失败直接抛出，避免 Mongo 不可用时导出零行 CSV 欺骗运营。
    public function findListIteratorStrict(array $filters = [], int $limit = 0): \Generator
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $query = self::buildQuery($filters);
        $options = ['sort' => ['created_at' => -1]];
        if ($limit > 0) {
            $options['limit'] = $limit;
        }
        $cursor = $db->selectCollection('questions')->find($query, $options);
        foreach ($cursor as $doc) {
            yield $this->docToArray($doc);
        }
    }

    public function search(string $keyword): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $escapedKeyword = preg_quote(trim($keyword), '/');
            $cursor = $db->selectCollection('questions')->find([
                '$or' => [
                    ['stem' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                    ['answer_text' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                    ['keywords' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                ],
            ], [
                'limit' => 100,
            ]);
            $rows = [];
            foreach ($cursor as $doc) {
                $row = $this->docToArray($doc);
                $row['score'] = 100;
                $rows[] = $row;
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] search failed: " . $e->getMessage());
            return [];
        }
    }

    // 搜索主链路兜底：ES 为空时走 Mongo 正则。Mongo 连接失败/查询异常抛出，让 SearchService 暴露为 50001。
    // 避免"ES 挂 + Mongo 挂 → 用户看到空列表"的故障伪装。
    public function searchStrict(string $keyword): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $escapedKeyword = preg_quote(trim($keyword), '/');
        $cursor = $db->selectCollection('questions')->find([
            '$or' => [
                ['stem' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                ['answer_text' => ['$regex' => $escapedKeyword, '$options' => 'i']],
                ['keywords' => ['$regex' => $escapedKeyword, '$options' => 'i']],
            ],
        ], [
            'limit' => 100,
        ]);
        $rows = [];
        foreach ($cursor as $doc) {
            $row = $this->docToArray($doc);
            $row['score'] = 100;
            $rows[] = $row;
        }
        return $rows;
    }

    public function update(string $questionId, array $data): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $db->selectCollection('questions')->updateOne(
                ['question_id' => $questionId],
                ['$set' => $data]
            );
            return $this->findByQuestionId($questionId);
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] update failed: " . $e->getMessage());
            return [];
        }
    }

    // 管理端 update 用：连接失败/查询异常抛出而非静默返回 []，避免 Mongo 故障被误报成"题目不存在"。
    // 返回值语义：正常更新返回 findByQuestionIdStrict 结果；记录不存在返回 [] 由 service 层判 40001。
    public function updateStrict(string $questionId, array $data): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $db->selectCollection('questions')->updateOne(
            ['question_id' => $questionId],
            ['$set' => $data]
        );
        if ($result->getMatchedCount() === 0) {
            return [];
        }
        return $this->findByQuestionIdStrict($questionId);
    }

    public function delete(string $questionId): bool
    {
        $db = MongoClient::connection();
        if (!$db) {
            return false;
        }
        try {
            $result = $db->selectCollection('questions')->deleteOne(['question_id' => $questionId]);
            return $result->getDeletedCount() > 0;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] delete failed: " . $e->getMessage());
            return false;
        }
    }

    // 管理端 delete 用：连接失败/异常抛出；返回 false 仅代表"记录不存在"（0 行被删）。
    public function deleteStrict(string $questionId): bool
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }
        $result = $db->selectCollection('questions')->deleteOne(['question_id' => $questionId]);
        return $result->getDeletedCount() > 0;
    }

    public function importFromJsonl(string $filePath, string $taskNo): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            throw new \RuntimeException('MongoDB connection unavailable');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $collection = $db->selectCollection('questions');
        // 去重的硬前提是 md5 / question_id 上的 Mongo 唯一索引存在。
        // 之前只靠 docs/03-数据库设计.md 记载的 createIndex 命令由运维手工执行，一旦部署时漏建，
        // insertMany 不会撞 BulkWriteException，重试导入/采集流水就会产生同题重复入库。
        // 首次导入时同步建一次（createIndex 同 spec 幂等，后续调用是 no-op）。
        $this->ensureIndexes($collection);
        $now = date('Y-m-d H:i:s');
        $imported = 0;
        $skipped = 0;
        $duplicated = 0;
        $failed = 0;
        $failedReasons = [];
        $batch = [];
        $batchSize = 100;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $item = json_decode($line, true);
            if (!$item || empty($item['question'])) {
                $skipped++;
                continue;
            }

            $doc = $this->buildDocFromRaw($item, $taskNo, $now);
            $batch[] = $doc;

            if (count($batch) >= $batchSize) {
                $res = $this->insertBatchDedup($collection, $batch);
                $imported += $res['inserted'];
                $duplicated += $res['duplicated'];
                $failed += $res['failed'];
                foreach ($res['reasons'] as $reason) {
                    if (count($failedReasons) < 20) {
                        $failedReasons[] = $reason;
                    }
                }
                $batch = [];
            }
        }
        fclose($handle);

        if (!empty($batch)) {
            $res = $this->insertBatchDedup($collection, $batch);
            $imported += $res['inserted'];
            $duplicated += $res['duplicated'];
            $failed += $res['failed'];
            foreach ($res['reasons'] as $reason) {
                if (count($failedReasons) < 20) {
                    $failedReasons[] = $reason;
                }
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicated' => $duplicated,
            'failed' => $failed,
            'failed_reasons' => $failedReasons,
        ];
    }

    // 供运维脚本/首次导入路径调用，幂等建索引。createIndex 对同 keys+options 反复调用是 no-op；
    // 若存在冲突的旧索引（名字同但 options 不同）会抛 IndexOptionsConflict，此时只记日志不中断，
    // 交由运维按 docs/03-数据库设计.md 指引 drop 旧索引。
    public function ensureIndexes($collection = null): void
    {
        if ($collection === null) {
            $db = MongoClient::connection();
            if (!$db) {
                throw new \RuntimeException('MongoDB connection unavailable');
            }
            $collection = $db->selectCollection('questions');
        }
        $specs = [
            ['keys' => ['question_id' => 1], 'options' => ['unique' => true, 'name' => 'uk_question_id']],
            ['keys' => ['md5' => 1],         'options' => ['unique' => true, 'name' => 'uk_md5']],
            ['keys' => ['category_id' => 1, 'status' => 1], 'options' => ['name' => 'ix_category_status']],
            ['keys' => ['source_id' => 1],   'options' => ['name' => 'ix_source_id']],
        ];
        foreach ($specs as $spec) {
            try {
                $collection->createIndex($spec['keys'], $spec['options']);
            } catch (\Throwable $e) {
                error_log("[QuestionRepository] ensureIndexes {$spec['options']['name']} failed: " . $e->getMessage());
            }
        }
    }

    protected function buildDocFromRaw(array $item, string $taskNo, string $now): array
    {
        $quetype = $item['quetype'] ?? '';
        $typeInfo = self::TYPE_MAP[$quetype] ?? ['code' => 'single', 'name' => $quetype];

        $question = trim($item['question'] ?? '');
        $optionsRaw = trim($item['options'] ?? '');
        $answer = trim($item['answer'] ?? '');

        $md5 = md5($question . $optionsRaw . $answer);
        $options = $this->parseOptions($optionsRaw);

        return [
            'question_id' => $this->generateQuestionId(),
            'md5' => $md5,
            'type_code' => $typeInfo['code'],
            'type_name' => $typeInfo['name'],
            'source_name' => '系统采集',
            'course_name' => $item['course_name'] ?? '',
            'course_id' => $item['course_id'] ?? '',
            'task_no' => $taskNo,
            'stem' => $question,
            'stem_plain' => $question,
            'options' => $options,
            'options_text' => $optionsRaw,
            'answer_text' => $answer,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function parseOptions(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/\s*\|\s*/', $raw);
        $options = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^([A-Z])\.(.+)$/', $part, $m)) {
                $options[] = ['label' => $m[1], 'content' => trim($m[2])];
            } else {
                $options[] = ['label' => '', 'content' => $part];
            }
        }
        return $options;
    }

    protected function insertBatchDedup($collection, array $batch): array
    {
        if (empty($batch)) {
            return ['inserted' => 0, 'duplicated' => 0, 'failed' => 0, 'reasons' => []];
        }
        try {
            $result = $collection->insertMany($batch, ['ordered' => false]);
            return ['inserted' => $result->getInsertedCount(), 'duplicated' => 0, 'failed' => 0, 'reasons' => []];
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $writeResult = $e->getWriteResult();
            $inserted = $writeResult->getInsertedCount();
            $writeErrors = $writeResult->getWriteErrors();
            $duplicated = 0;
            $failed = 0;
            $reasons = [];
            foreach ($writeErrors as $err) {
                // code 11000 = duplicate key。唯一索引正常工作时的预期行为，不能和真 IO 故障混算。
                // 索引缺失 → 不会抛 11000 也不会抛 BulkWriteException，重复题会被当作正常 insert，
                // 这也是 ensureIndexes() 在导入入口必须幂等调用的原因。
                if ((int) $err->getCode() === 11000) {
                    $duplicated++;
                } else {
                    $failed++;
                    $reasons[] = $err->getMessage();
                }
            }
            // 对齐：inserted + duplicated + failed 应等于批大小（mongo 未返回的尾部按 failed 兜底）。
            $tail = count($batch) - $inserted - $duplicated - $failed;
            if ($tail > 0) {
                $failed += $tail;
            }
            return ['inserted' => $inserted, 'duplicated' => $duplicated, 'failed' => $failed, 'reasons' => $reasons];
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] insertBatchDedup failed: " . $e->getMessage());
            return ['inserted' => 0, 'duplicated' => 0, 'failed' => count($batch), 'reasons' => [$e->getMessage()]];
        }
    }

    protected function generateQuestionId(): string
    {
        return 'Q' . date('YmdHis') . bin2hex(random_bytes(4));
    }

    public function findByTaskNo(string $taskNo, int $limit = 0): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $options = ['sort' => ['created_at' => -1]];
            if ($limit > 0) {
                $options['limit'] = $limit;
            }
            $cursor = $db->selectCollection('questions')->find(
                ['task_no' => $taskNo],
                $options
            );
            $rows = [];
            foreach ($cursor as $doc) {
                $rows[] = $this->docToArray($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByTaskNo failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 按 task_no 聚合，每个 course_id 下的题目数。
     * 给"采集任务详情 → 查看课程"用，前端展示每门课的题目数列。
     * 返回 ['course_id_1' => 12, 'course_id_2' => 5]，Mongo 不可用 / 失败时返 []。
     */
    public function countByTaskNoGroupByCourse(string $taskNo): array
    {
        $db = MongoClient::connection();
        if (!$db) {
            return [];
        }
        try {
            $cursor = $db->selectCollection('questions')->aggregate([
                ['$match' => ['task_no' => $taskNo]],
                ['$group' => ['_id' => '$course_id', 'count' => ['$sum' => 1]]],
            ]);
            $map = [];
            foreach ($cursor as $row) {
                $cid = (string) ($row['_id'] ?? '');
                if ($cid === '') {
                    continue;
                }
                $map[$cid] = (int) ($row['count'] ?? 0);
            }
            return $map;
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] countByTaskNoGroupByCourse failed: " . $e->getMessage());
            return [];
        }
    }

    // 采集导入后的 ES 索引回灌用：按 task_no 流式 yield，避免一次性把整批题目（可能 10W+）全部堆到 PHP 内存。
    // 调用方负责在外层做 2000 条一批的 bulkIndex。
    public function findByTaskNoIterator(string $taskNo): \Generator
    {
        $db = MongoClient::connection();
        if (!$db) {
            return;
        }
        try {
            $cursor = $db->selectCollection('questions')->find(
                ['task_no' => $taskNo],
                ['sort' => ['created_at' => -1]]
            );
            foreach ($cursor as $doc) {
                yield $this->docToArray($doc);
            }
        } catch (\Throwable $e) {
            error_log("[QuestionRepository] findByTaskNoIterator failed: " . $e->getMessage());
        }
    }

    protected static function buildQuery(array $filters): array
    {
        $query = [];
        $stem = trim((string) ($filters['stem'] ?? ''));
        if ($stem !== '') {
            $query['stem'] = ['$regex' => preg_quote($stem, '/'), '$options' => 'i'];
        }
        return $query;
    }

    protected function docToArray($doc): array
    {
        if ($doc instanceof \MongoDB\Model\BSONDocument) {
            $arr = (array) $doc->getArrayCopy();
            if (isset($arr['_id'])) {
                $arr['_id'] = (string) $arr['_id'];
            }
            foreach ($arr as $k => $v) {
                if ($v instanceof \MongoDB\Model\BSONArray) {
                    $arr[$k] = $v->getArrayCopy();
                } elseif ($v instanceof \MongoDB\Model\BSONDocument) {
                    $arr[$k] = (array) $v->getArrayCopy();
                }
            }
            return $arr;
        }
        return is_array($doc) ? $doc : (array) $doc;
    }
}
