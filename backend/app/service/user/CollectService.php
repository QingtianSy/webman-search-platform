<?php

namespace app\service\user;

use app\exception\BusinessException;
use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskDetailRepository;
use app\repository\mysql\CollectTaskRepository;
use app\repository\mysql\ProxyRepository;
use app\repository\mysql\SystemConfigRepository;
use app\service\proxy\CollegeService;
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

    public function detail(int $userId, string $taskNo): array
    {
        return (new CollectTaskDetailRepository())->findByTaskNo($taskNo, $userId);
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
            'schoolName' => $login['schoolName'] ?? '',
            'courseCount' => count($courses),
            'courses' => $courses,
        ];
    }

    public function submitCollect(int $userId, array $data): array
    {
        $taskNo = 'CT' . date('Ymd') . '-' . bin2hex(random_bytes(4));

        $schoolName = $data['school_name'] ?? '';
        $province = '';
        $city = '';
        $proxyUrl = '';

        $configRepo = new SystemConfigRepository();
        $configs = $configRepo->getByGroup('collect');
        $configMap = [];
        foreach ($configs as $c) {
            $configMap[$c['config_key']] = $c['config_value'];
        }
        $proxyEnabled = (int) ($configMap['collect_proxy_enabled'] ?? 0);
        $cooldownMin = (int) ($configMap['collect_proxy_cooldown_min'] ?? 5);

        if ($schoolName !== '') {
            $collegeService = new CollegeService();
            $location = $collegeService->lookup($schoolName);
            $province = $location['province'];
            $city = $location['city'];
        }

        if ($proxyEnabled && ($province !== '' || $city !== '')) {
            $proxyRepo = new ProxyRepository();
            $proxy = $proxyRepo->findByLocation($province, $city, $cooldownMin);
            if (!empty($proxy)) {
                $proxyRepo->markUsed((int) $proxy['id']);
                $auth = '';
                if (!empty($proxy['username'])) {
                    $auth = urlencode($proxy['username']);
                    if (!empty($proxy['password'])) {
                        $auth .= ':' . urlencode($proxy['password']);
                    }
                    $auth .= '@';
                }
                $proxyUrl = "{$proxy['protocol']}://{$auth}{$proxy['host']}:{$proxy['port']}";
            }
        }

        (new CollectTaskRepository())->create([
            'task_no' => $taskNo,
            'user_id' => $userId,
            'account_id' => 0,
            'account_phone' => $data['account'],
            'account_password' => $data['password'],
            'collect_type' => $data['collect_type'],
            'course_ids' => $data['course_ids'],
            'course_count' => $data['course_count'],
            'school_name' => $schoolName,
            'province' => $province,
            'city' => $city,
            'proxy_url' => $proxyUrl,
        ]);

        return ['task_no' => $taskNo];
    }
}
