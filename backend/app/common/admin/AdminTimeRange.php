<?php

namespace app\common\admin;

class AdminTimeRange
{
    public static function parse(array $input): array
    {
        return [
            'start_time' => trim((string) ($input['start_time'] ?? '')),
            'end_time' => trim((string) ($input['end_time'] ?? '')),
        ];
    }
}
