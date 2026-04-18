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
}
