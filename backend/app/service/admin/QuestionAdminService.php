<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\service\question\QuestionService;
use app\repository\mongo\QuestionRepository;
use app\service\question\QuestionIndexService;

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

        $list = (new QuestionService())->getList($filters)['list'] ?? [];
        return AdminListBuilder::make($list, $page, $pageSize);
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
        if ($db) {
            $db->selectCollection('questions')->insertOne($data);
        }
        (new QuestionIndexService())->sync($data['question_id']);

        return [
            'created' => true,
            'data' => $data,
        ];
    }

    public function update(string $id, array $data): array
    {
        $result = (new QuestionRepository())->update($id, $data);
        if (!empty($result)) {
            (new QuestionIndexService())->sync($id);
        }
        return $result;
    }

    public function delete(string $id): array
    {
        $deleted = (new QuestionRepository())->delete($id);
        (new QuestionIndexService())->delete($id);
        return ['deleted' => $deleted];
    }

    public function export(array $query = []): array
    {
        $filters = [
            'stem' => trim((string) ($query['keyword'] ?? '')),
        ];
        $list = (new QuestionService())->getList($filters)['list'] ?? [];

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

        return [$headers, $rows];
    }
}
