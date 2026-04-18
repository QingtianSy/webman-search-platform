<?php

namespace app\service\user;

use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;

class CollectService
{
    public function accounts(int $userId): array
    {
        return (new CollectAccountRepository())->listByUserId($userId);
    }

    public function tasks(int $userId): array
    {
        return (new CollectTaskRepository())->listByUserId($userId);
    }

    public function detail(string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo);
    }
}
