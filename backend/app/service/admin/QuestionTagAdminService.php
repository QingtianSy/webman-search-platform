<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\QuestionTag;
use support\Pagination;

class QuestionTagAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20, 'keyword' => ''];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = QuestionTag::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        $row = new QuestionTag();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = QuestionTag::query()->find($id);
        if (!$row) {
            throw new BusinessException('标签不存在', 40001);
        }
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = QuestionTag::query()->find($id);
        if (!$row) {
            throw new BusinessException('标签不存在', 40001);
        }
        $row->delete();
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
