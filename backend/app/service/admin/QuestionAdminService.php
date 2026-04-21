<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mongo\QuestionRepository;
use app\service\question\QuestionIndexService;
use support\Pagination;

class QuestionAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $page = max(1, (int) $query['page']);
        $pageSize = min(200, max(1, (int) $query['page_size']));

        $filters = [
            'stem' => (string) ($query['keyword'] ?? ''),
        ];

        // 管理端走 strict 路径：Mongo 不可用或查询异常直接抛 50001，
        // 不再把故障伪装成"空列表"误导排障。
        $repo = new QuestionRepository();
        try {
            $total = $repo->countByFiltersStrict($filters);
            $list = $repo->findPageStrict($filters, $page, $pageSize);
        } catch (\Throwable $e) {
            error_log("[QuestionAdminService] getList failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function detail(string $id): array
    {
        try {
            return (new QuestionRepository())->findByQuestionIdStrict($id);
        } catch (\Throwable $e) {
            error_log("[QuestionAdminService] detail failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }
    }

    public function create(array $data): array
    {
        $data['question_id'] = 'Q' . date('YmdHis') . bin2hex(random_bytes(4));
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;
        $data['stem_plain'] = $data['stem'] ?? '';
        $data['md5'] = md5(($data['stem'] ?? '') . ($data['options_text'] ?? '') . ($data['answer_text'] ?? ''));

        $db = \support\adapter\MongoClient::connection();
        if (!$db) {
            throw new BusinessException('MongoDB 连接不可用，无法创建题目', 50001);
        }
        $db->selectCollection('questions')->insertOne($data);

        $esSynced = (new QuestionIndexService())->sync($data['question_id']);
        if (!$esSynced) {
            error_log("[QuestionAdminService] ES sync failed after create: {$data['question_id']}");
        }

        $result = [
            'created' => true,
            'data' => $data,
            'es_synced' => $esSynced,
        ];
        if (!$esSynced) {
            $result['es_warning'] = 'ES索引同步失败，搜索结果可能暂时不包含此题目';
        }
        return $result;
    }

    public function update(string $id, array $data): array
    {
        $repo = new QuestionRepository();
        if (isset($data['stem']) || isset($data['options_text']) || isset($data['answer_text'])) {
            // 前置读原记录用于 md5/stem_plain 重算。走 strict 版：
            // Mongo 异常时直接抛 50001，避免静默返回 [] 让 md5 被按空串错算写回。
            try {
                $existing = $repo->findByQuestionIdStrict($id);
            } catch (\Throwable $e) {
                error_log("[QuestionAdminService] update prefetch failed: " . $e->getMessage());
                throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
            }
            if (empty($existing)) {
                throw new BusinessException('题目不存在', 40001);
            }
            $stem = $data['stem'] ?? ($existing['stem'] ?? '');
            $options = $data['options_text'] ?? ($existing['options_text'] ?? '');
            $answer = $data['answer_text'] ?? ($existing['answer_text'] ?? '');
            $data['stem_plain'] = $stem;
            $data['md5'] = md5($stem . $options . $answer);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        try {
            $result = $repo->updateStrict($id, $data);
        } catch (\Throwable $e) {
            error_log("[QuestionAdminService] update failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }
        if (empty($result)) {
            throw new BusinessException('题目不存在', 40001);
        }
        $esSynced = (new QuestionIndexService())->sync($id);
        if (!$esSynced) {
            error_log("[QuestionAdminService] ES sync failed after update: {$id}");
        }
        $result['es_synced'] = $esSynced;
        if (!$esSynced) {
            $result['es_warning'] = 'ES索引同步失败，搜索结果可能暂时不包含此题目';
        }
        return $result;
    }

    public function delete(string $id): array
    {
        try {
            $deleted = (new QuestionRepository())->deleteStrict($id);
        } catch (\Throwable $e) {
            error_log("[QuestionAdminService] delete failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }
        if (!$deleted) {
            throw new BusinessException('题目不存在', 40001);
        }
        $esSynced = (new QuestionIndexService())->delete($id);
        if (!$esSynced) {
            error_log("[QuestionAdminService] ES delete failed after delete: {$id}");
        }
        $result = ['deleted' => true, 'es_synced' => $esSynced];
        if (!$esSynced) {
            $result['es_warning'] = 'ES索引删除失败，搜索结果可能仍显示已删除题目';
        }
        return $result;
    }

    public function export(array $query = []): array
    {
        $filters = [
            'stem' => trim((string) ($query['keyword'] ?? '')),
        ];
        $exportLimit = 50000;
        $repo = new QuestionRepository();
        // export 开头先用 strict count 验 Mongo 可用，避免静默导出零行 CSV。
        try {
            $total = $repo->countByFiltersStrict($filters);
        } catch (\Throwable $e) {
            error_log("[QuestionAdminService] export count failed: " . $e->getMessage());
            throw new BusinessException('题库数据源暂不可用，请稍后重试', 50001);
        }

        $headers = ['题目ID', 'MD5', '题型', '来源', '课程', '题干', '选项', '答案', '状态', '创建时间'];
        $rows = (function () use ($repo, $filters, $exportLimit) {
            foreach ($repo->findListIteratorStrict($filters, $exportLimit) as $r) {
                yield [
                    $r['question_id'] ?? '',
                    $r['md5'] ?? '',
                    $r['type_name'] ?? '',
                    $r['source_name'] ?? '',
                    $r['course_name'] ?? '',
                    $r['stem_plain'] ?? ($r['stem'] ?? ''),
                    $r['options_text'] ?? '',
                    $r['answer_text'] ?? '',
                    ((int) ($r['status'] ?? 1)) === 1 ? '正常' : '停用',
                    $r['created_at'] ?? '',
                ];
            }
        })();

        return [$headers, $rows, $total, $exportLimit];
    }
}
