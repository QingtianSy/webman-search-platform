<?php

namespace app\service\admin;

use app\exception\BusinessException;
use app\repository\mysql\DocArticleRepository;
use support\Pagination;

class DocAdminService
{
    public function getList(array $query = []): array
    {
        $query += ['page' => 1, 'page_size' => 20];
        $repo = new DocArticleRepository();
        $total = $repo->countAll();
        $list = $repo->findPage((int) $query['page'], (int) $query['page_size']);
        return Pagination::format($list, $total, (int) $query['page'], (int) $query['page_size']);
    }

    public function create(array $data): array
    {
        $row = (new DocArticleRepository())->create($data);
        if (isset($row['error'])) {
            if ($row['error'] === 'duplicate_slug') {
                throw new BusinessException('slug 已存在', 40001);
            }
            throw new BusinessException('文档创建失败', 40001);
        }
        if (empty($row) || empty($row['id'])) {
            throw new BusinessException('文档创建失败', 40001);
        }
        return [
            'success' => true,
            'action' => 'create',
            'id' => $row['id'],
            'data' => $row,
        ];
    }

    public function update(int $id, array $data): array
    {
        $row = (new DocArticleRepository())->update($id, $data);
        if (isset($row['error'])) {
            if ($row['error'] === 'duplicate_slug') {
                throw new BusinessException('slug 已存在', 40001);
            }
            throw new BusinessException('文档更新失败', 40001);
        }
        if (empty($row)) {
            throw new BusinessException('文档不存在', 40001);
        }
        return [
            'success' => true,
            'action' => 'update',
            'id' => $id,
            'data' => $row,
        ];
    }

    public function delete(int $id): array
    {
        $ok = (new DocArticleRepository())->delete($id);
        if (!$ok) {
            throw new BusinessException('文档不存在', 40001);
        }
        return [
            'success' => true,
            'action' => 'delete',
            'id' => $id,
        ];
    }
}
