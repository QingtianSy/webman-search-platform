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
                'name' => config('app.name', 'webman-search-platform'),
                'env' => config('app.env', 'dev'),
                'debug' => (bool) config('app.debug', true),
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
