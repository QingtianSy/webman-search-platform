<?php

namespace app\service\system;

use support\adapter\ElasticsearchClient;
use support\adapter\MongoClient;
use support\adapter\MySqlClient;
use support\adapter\RedisClient;

class HealthService
{
    public function detail(): array
    {
        return [
            'app' => [
                'name' => env('APP_NAME', 'webman-search-platform'),
                'env' => env('APP_ENV', 'dev'),
                'debug' => (bool) env('APP_DEBUG', true),
            ],
            'services' => [
                'mysql' => MySqlClient::isConfigured(),
                'redis' => RedisClient::isConfigured(),
                'mongodb' => MongoClient::isConfigured(),
                'elasticsearch' => ElasticsearchClient::isConfigured(),
            ],
        ];
    }
}
