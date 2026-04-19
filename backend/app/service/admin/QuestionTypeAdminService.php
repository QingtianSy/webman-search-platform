<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\model\admin\QuestionType;

class QuestionTypeAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20, 'keyword' => ''];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = QuestionType::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }

    public function create(array $data): array
    {
        $row = new QuestionType();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = QuestionType::query()->find($id);
        if (!$row) {
            return [];
        }
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = QuestionType::query()->find($id);
        if ($row) {
            $row->delete();
        }
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
