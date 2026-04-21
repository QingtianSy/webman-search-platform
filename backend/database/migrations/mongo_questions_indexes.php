<?php

// MongoDB 索引初始化脚本（题库 questions 集合）。
//
// 去重的硬前提是 md5 / question_id 上存在唯一索引。之前唯一索引只写在 docs/03-数据库设计.md，
// 生产部署若漏建，采集导入/重试会默默插入重复题。
//
// 首次导入时 QuestionRepository::importFromJsonl 会幂等调用 ensureIndexes()，
// 但首次导入之前（如预部署验收）需要运维直接建，此脚本就是那个"可执行建索引脚本"。
//
// 用法：
//   php database/migrations/mongo_questions_indexes.php
// 需先通过 composer 安装依赖；脚本依赖 Webman 的 support\bootstrap。

require_once __DIR__ . '/../../vendor/autoload.php';

use support\adapter\MongoClient;

$db = MongoClient::connection();
if (!$db) {
    fwrite(STDERR, "[mongo_questions_indexes] MongoDB connection unavailable\n");
    exit(1);
}

$collection = $db->selectCollection('questions');
$specs = [
    ['keys' => ['question_id' => 1], 'options' => ['unique' => true, 'name' => 'uk_question_id']],
    ['keys' => ['md5' => 1],         'options' => ['unique' => true, 'name' => 'uk_md5']],
    ['keys' => ['category_id' => 1, 'status' => 1], 'options' => ['name' => 'ix_category_status']],
    ['keys' => ['source_id' => 1],   'options' => ['name' => 'ix_source_id']],
];

$failed = 0;
foreach ($specs as $spec) {
    try {
        $name = $collection->createIndex($spec['keys'], $spec['options']);
        echo "[mongo_questions_indexes] ok: {$name}\n";
    } catch (\Throwable $e) {
        $failed++;
        fwrite(STDERR, "[mongo_questions_indexes] {$spec['options']['name']} failed: " . $e->getMessage() . "\n");
    }
}

exit($failed > 0 ? 2 : 0);
