<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\service\question\QuestionService;
use app\repository\mongo\QuestionRepository;
use app\service\question\QuestionIndexService;
use support\Pagination;

class QuestionAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'page' => 1, 'page_size' => 20];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $filters = [
            'stem' => (string) ($query['keyword'] ?? ''),
        ];

        $repo = new QuestionRepository();
        $total = $repo->countByFilters($filters);
        $list = $repo->findPage($filters, $page, $pageSize);
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function detail(string $id): array
    {
        return (new QuestionService())->detail($id);
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
        $result = (new QuestionRepository())->update($id, $data);
        if (empty($result)) {
            throw new BusinessException('题目不存在', 40001);
        }
        $esSynced = (new QuestionIndexService())->sync($id);
        if (!$esSynced) {
            error_log("[QuestionAdminService] ES sync failed after update: {$id}");
        }
        $result['es_synced'] = $esSynced;
        if (!$esSynced) {
            $result['es_warning'] = 'ES索引同步失败，搜索结果可能暂时不���含���题目';
        }
        return $result;
    }

    public function delete(string $id): array
    {
        $deleted = (new QuestionRepository())->delete($id);
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
        $total = $repo->countByFilters($filters);
        $list = $repo->findList($filters, $exportLimit);

        $headers = ['题目ID', 'MD5', '题型', '来源', '课程', '题干', '选项', '答案', '状态', '创建时间'];
        $rows = array_map(fn($r) => [
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
        ], $list);

        return [$headers, $rows, $total, $exportLimit];
    }
}
