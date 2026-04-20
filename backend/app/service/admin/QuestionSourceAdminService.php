<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\model\admin\QuestionSource;
use support\Pagination;

class QuestionSourceAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20, 'keyword' => ''];
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $builder = QuestionSource::query();
        if ($keyword !== '') {
            $builder->where('name', 'like', "%{$keyword}%");
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return Pagination::format($list, $total, $page, $pageSize);
    }

    public function create(array $data): array
    {
        if (QuestionSource::query()->where('code', $data['code'] ?? '')->exists()) {
            throw new BusinessException('来源编码已存在', 40001);
        }
        $row = new QuestionSource();
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'create', 'id' => $row->id, 'data' => $row->toArray()];
    }

    public function update(int $id, array $data): array
    {
        $row = QuestionSource::query()->find($id);
        if (!$row) {
            throw new BusinessException('来源不存在', 40001);
        }
        if (!empty($data['code']) && $data['code'] !== $row->code) {
            if (QuestionSource::query()->where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw new BusinessException('来源编码已存在', 40001);
            }
        }
        unset($data['id']);
        $row->fill($data);
        $row->save();
        return ['success' => true, 'action' => 'update', 'id' => $id, 'data' => $row->toArray()];
    }

    public function delete(int $id): array
    {
        $row = QuestionSource::query()->find($id);
        if (!$row) {
            throw new BusinessException('来源不存在', 40001);
        }
        $row->delete();
        return ['success' => true, 'action' => 'delete', 'id' => $id];
    }
}
