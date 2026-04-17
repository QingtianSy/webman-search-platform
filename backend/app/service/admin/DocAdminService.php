<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\model\admin\DocArticle;
use app\repository\mysql\DocArticleRepository;

class DocAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20];
        return config('integration.docs_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new DocArticleRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['title'] ?? ''), $keyword)
                    || str_contains((string) ($row['slug'] ?? ''), $keyword);
            }));
        }
        $list = AdminStatusFilter::apply($list, $query['status']);
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    protected function getListReal(array $query): array
    {
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];
        $keyword = trim((string) $query['keyword']);
        $status = $query['status'];

        $builder = DocArticle::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }

    public function create(array $data): array
    {
        if (config('integration.docs_source', 'mock') === 'real') {
            $row = new DocArticle();
            $row->fill($data);
            $row->save();
            return $row->toArray();
        }
        return (new DocArticleRepository())->create($data);
    }

    public function update(int $id, array $data): array
    {
        if (config('integration.docs_source', 'mock') === 'real') {
            $row = DocArticle::query()->find($id);
            if (!$row) {
                return [];
            }
            $row->fill($data);
            $row->save();
            return $row->toArray();
        }
        return (new DocArticleRepository())->update($id, $data);
    }

    public function delete(int $id): array
    {
        if (config('integration.docs_source', 'mock') === 'real') {
            $row = DocArticle::query()->find($id);
            if ($row) {
                $row->delete();
            }
            return ['deleted' => true, 'id' => $id];
        }
        return ['deleted' => true, 'id' => $id];
    }
}
