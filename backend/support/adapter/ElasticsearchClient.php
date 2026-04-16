<?php

namespace support\adapter;

class ElasticsearchClient
{
    public static function config(): array
    {
        return config('elasticsearch', []);
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']);
    }

    public static function questionIndex(): string
    {
        return (string) (self::config()['index']['question'] ?? 'question_index');
    }

    public static function baseUri(): string
    {
        return rtrim((string) (self::config()['host'] ?? ''), '/');
    }

    public static function auth(): array
    {
        return [
            'username' => (string) (self::config()['username'] ?? ''),
            'password' => (string) (self::config()['password'] ?? ''),
        ];
    }

    public static function sslOptions(): array
    {
        return [
            'verify' => false,
        ];
    }
}
