<?php

namespace app\service\admin;

use app\common\admin\AdminListBuilder;
use app\repository\mysql\AnnouncementRepository;

class AnnouncementAdminService
{
    public function getList(array $query = []): array
    {
        $page = $query['page'] ?? 1;
        $pageSize = $query['page_size'] ?? 20;
        $list = (new AnnouncementRepository())->latest();
        return AdminListBuilder::make($list, $page, $pageSize);
    }

    public function create(array $data): array
    {
        return (new AnnouncementRepository())->create($data);
    }

    public function update(int $id, array $data): array
    {
        return (new AnnouncementRepository())->update($id, $data);
    }

    public function delete(int $id): array
    {
        return ['deleted' => true, 'id' => $id];
    }
}
