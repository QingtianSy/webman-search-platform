<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use support\adapter\ChaoxingClient;
use support\ResponseCode;

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

    public function queryCourses(string $account, string $password): array
    {
        $client = new ChaoxingClient();
        $login = $client->login($account, $password);
        if (!$login['success']) {
            throw new BusinessException($login['msg'], ResponseCode::PARAM_ERROR);
        }
        $courses = $client->queryCourses();
        return [
            'userName' => $login['userName'],
            'courseCount' => count($courses),
            'courses' => $courses,
        ];
    }

    public function submitCollect(int $userId, array $data): array
    {
        $taskNo = 'CT' . date('Ymd') . '-' . bin2hex(random_bytes(4));
        (new CollectTaskRepository())->create([
            'task_no' => $taskNo,
            'user_id' => $userId,
            'account_id' => 0,
            'account_phone' => $data['account'],
            'account_password' => $data['password'],
            'collect_type' => $data['collect_type'],
            'course_ids' => $data['course_ids'],
            'course_count' => $data['course_count'],
        ]);
        return ['task_no' => $taskNo];
    }
}
