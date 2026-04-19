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
        $data['question_id'] = $data['question_id'] ?? 'Q' . date('YmdHis') . bin2hex(random_bytes(4));
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;

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
}
