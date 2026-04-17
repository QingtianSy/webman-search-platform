<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\common\admin\AdminStatusFilter;
use app\model\admin\ApiSource;
use app\repository\mysql\ApiSourceRepository;

class ApiSourceAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['keyword' => '', 'status' => null, 'page' => 1, 'page_size' => 20];
        return config('integration.api_source_source', 'mock') === 'real'
            ? $this->getListReal($query)
            : $this->getListMock($query);
    }

    protected function getListMock(array $query): array
    {
        $keyword = trim((string) $query['keyword']);
        $page = (int) $query['page'];
        $pageSize = (int) $query['page_size'];

        $list = (new ApiSourceRepository())->all();
        if ($keyword !== '') {
            $list = array_values(array_filter($list, function ($row) use ($keyword) {
                return str_contains((string) ($row['name'] ?? ''), $keyword)
                    || str_contains((string) ($row['url'] ?? ''), $keyword);
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

        $builder = ApiSource::query();
        if ($keyword !== '') {
            $builder->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('url', 'like', "%{$keyword}%");
            });
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', $status);
        }
        $total = $builder->count();
        $list = $builder->orderBy('id', 'desc')->forPage($page, $pageSize)->get()->toArray();
        return AdminListBuilder::make($list, $page, $pageSize) + ['total' => $total];
    }

    public function detail(int $id): array
    {
        if (config('integration.api_source_source', 'mock') === 'real') {
            $row = ApiSource::query()->find($id);
            return $row ? $row->toArray() : [];
        }
        return (new ApiSourceRepository())->findById($id);
    }

    public function test(int $id): array
    {
        return (new ApiSourceRepository())->test($id);
    }
}
