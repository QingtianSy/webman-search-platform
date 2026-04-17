<?php

namespace app\validate\admin;

use app\exception\BusinessException;
use support\ResponseCode;

class CollectTaskValidate
{
    public function taskNo(array $data): string
    {
        $taskNo = trim((string) ($data['task_no'] ?? ''));
        if ($taskNo === '') {
            throw new BusinessException('任务号不能为空', ResponseCode::PARAM_ERROR);
        }
        return $taskNo;
    }
}
