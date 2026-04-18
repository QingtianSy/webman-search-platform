<?php

namespace app\validate;

class SearchQueryValidator
{
    public function validate(array $data): array
    {
        $q = trim((string) ($data['q'] ?? ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return [false, '搜索关键词最少2个字符'];
        }
        return [true, 'ok'];
    }
}
