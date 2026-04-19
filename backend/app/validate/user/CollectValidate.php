<?php

namespace app\validate\user;

use app\exception\BusinessException;
use support\ResponseCode;

class CollectValidate
{
    public function taskNo(array $data): string
    {
        $taskNo = trim((string) ($data['task_no'] ?? ''));
        if ($taskNo === '') {
            throw new BusinessException('任务号不能为空', ResponseCode::PARAM_ERROR);
        }
        return $taskNo;
    }

    public function queryCourses(array $data): array
    {
        $account = trim((string) ($data['account'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        if ($account === '' || $password === '') {
            throw new BusinessException('账号和密码不能为空', ResponseCode::PARAM_ERROR);
        }
        return compact('account', 'password');
    }

    public function submitCollect(array $data): array
    {
        $account = trim((string) ($data['account'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        if ($account === '' || $password === '') {
            throw new BusinessException('账号和密码不能为空', ResponseCode::PARAM_ERROR);
        }

        $collectType = trim((string) ($data['collect_type'] ?? 'courses'));
        $allowedTypes = ['courses', 'course', 'chapter', 'exam', 'homework'];
        if (!in_array($collectType, $allowedTypes, true)) {
            throw new BusinessException('无效的采集类型', ResponseCode::PARAM_ERROR);
        }

        $courseIds = trim((string) ($data['course_ids'] ?? ''));

        return [
            'account' => $account,
            'password' => $password,
            'collect_type' => $collectType,
            'course_ids' => $courseIds,
            'course_count' => $data['course_count'] ?? 0,
            'school_name' => trim((string) ($data['school_name'] ?? '')),
        ];
    }
}
