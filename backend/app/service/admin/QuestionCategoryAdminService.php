<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\model\admin\QuestionCategory;
use app\repository\mysql\QuestionCategoryRepository;

class QuestionCategoryAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20, 'keyword' => ''];
        return config('integration.question_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $list = (new QuestionCategoryRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, fn($row) => str_contains((string)($row['name'] ?? ''), $keyword)));
        }
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    protected function getListReal(array $query): array
    {
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = QuestionCategory::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }
}
