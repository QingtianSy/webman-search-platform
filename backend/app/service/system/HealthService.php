<?php

namespace app\service\system;

class HealthService
{
    public function detail(): array
    {
        return [
            'app' => [
                'name' => config('app.app_name', 'webman-search-platform'),
                'env' => config('app.env', 'prod'),
                'debug' => (bool) config('app.debug', false),
            ],
            'services' => [
                'mysql' => 'skipped',
                'redis' => 'skipped',
                'mongodb' => 'skipped',
                'elasticsearch' => 'skipped',
            ],
        ];
    }
}
