<?php

namespace app\validate\user;

use app\exception\BusinessException;
use support\ResponseCode;

class SearchValidate
{
    public function query(array $data): array
    {
        $q = trim((string) ($data['q'] ?? ''));
        if ($q === '') {
            throw new BusinessException('搜索关键词不能为空', ResponseCode::PARAM_ERROR);
        }

        return [
            'q' => $q,
            'info' => (string) ($data['info'] ?? ''),
            'split' => (string) ($data['split'] ?? '###'),
        ];
    }
}
