<?php

namespace app\service\user;

use app\repository\mysql\UserApiSourceRepository;

class ApiSourceService
{
    public function getList(int $userId, array $query = []): array
    {
        return (new UserApiSourceRepository())->findByUserId($userId, $query);
    }

    public function detail(int $userId, int $id): array
    {
        return (new UserApiSourceRepository())->findById($userId, $id);
    }

    public function create(int $userId, array $data): array
    {
        $repo = new UserApiSourceRepository();
        $id = $repo->create($userId, $data);
        if ($id <= 0) {
            return [];
        }
        return $repo->findById($userId, $id);
    }

    public function update(int $userId, int $id, array $data): array
    {
        $repo = new UserApiSourceRepository();
        $ok = $repo->update($userId, $id, $data);
        if (!$ok) {
            return [];
        }
        return $repo->findById($userId, $id);
    }

    public function delete(int $userId, int $id): bool
    {
        return (new UserApiSourceRepository())->delete($userId, $id);
    }

    public function test(int $userId, int $id): array
    {
        return (new UserApiSourceRepository())->test($userId, $id);
    }
}
