<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\service\question\QuestionService;
use app\repository\mongo\QuestionRepository;

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

    public function detail(int $id): array
    {
        return (new QuestionService())->detail($id);
    }

    public function create(array $data): array
    {
        return [
            'created' => true,
            'data' => $data,
        ];
    }

    public function update(int $id, array $data): array
    {
        return (new QuestionRepository())->update($id, $data);
    }

    public function delete(int $id): array
    {
        return ['deleted' => (new QuestionRepository())->delete($id)];
    }
}
