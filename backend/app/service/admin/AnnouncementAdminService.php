<?php

namespace app\service\admin;

use app\repository\mysql\AnnouncementRepository;
use app\common\admin\AdminListBuilder;

class AnnouncementAdminService
{
    public function getList(): array
    {
        $list = (new AnnouncementRepository())->latest();
        return AdminListBuilder::make($list);
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
