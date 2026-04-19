<?php

namespace app\service\system;

use support\adapter\MySqlClient;
use support\adapter\RedisClient;
use support\adapter\MongoClient;
use support\adapter\ElasticsearchClient;
use GuzzleHttp\Client;

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
                'mysql' => $this->checkMysql(),
                'redis' => $this->checkRedis(),
                'mongodb' => $this->checkMongo(),
                'elasticsearch' => $this->checkEs(),
            ],
        ];
    }

    protected function checkMysql(): string
    {
        try {
            $pdo = MySqlClient::pdo();
            if (!$pdo) {
                return 'disconnected';
            }
            $pdo->query('SELECT 1');
            return 'ok';
        } catch (\Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    protected function checkRedis(): string
    {
        try {
            $redis = RedisClient::connection();
            if (!$redis) {
                return 'not_configured';
            }
            $pong = $redis->ping();
            return $pong ? 'ok' : 'disconnected';
        } catch (\Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    protected function checkMongo(): string
    {
        if (!MongoClient::isConfigured()) {
            return 'not_configured';
        }
        try {
            $db = MongoClient::connection();
            if (!$db) {
                return 'disconnected';
            }
            $db->command(['ping' => 1]);
            return 'ok';
        } catch (\Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    protected function checkEs(): string
    {
        if (!ElasticsearchClient::isConfigured()) {
            return 'not_configured';
        }
        try {
            $client = new Client([
                'base_uri' => ElasticsearchClient::host(),
                'verify' => true,
                'auth' => [ElasticsearchClient::username(), ElasticsearchClient::password()],
                'timeout' => 5,
            ]);
            $response = $client->get('/');
            $data = json_decode((string) $response->getBody(), true);
            return !empty($data['cluster_name']) ? 'ok' : 'unknown';
        } catch (\Throwable $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
