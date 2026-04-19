<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\QuestionCategory;
use support\Pagination;
use support\ResponseCode;

class QuestionCategoryAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20, 'keyword' => ''];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = QuestionCategory::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        $row = new QuestionCategory();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = QuestionCategory::query()->find($id);
        if (!$row) {
            return [];
        }
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        if (QuestionCategory::query()->where('parent_id', $id)->exists()) {
            throw new BusinessException('该分类下存在子分类，无法删除', ResponseCode::PARAM_ERROR);
        }
        $row = QuestionCategory::query()->find($id);
        if ($row) {
            $row->delete();
        }
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
