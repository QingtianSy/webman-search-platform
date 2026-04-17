<?php

namespace support\adapter;

class ElasticsearchClient
{
    public static function config(): array
    {
        return function_exists('config') ? config('elasticsearch', []) : [];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return !empty($config['host']);
    }

    public static function host(): string
    {
        return (string) (self::config()['host'] ?? '');
    }

    public static function username(): string
    {
        return (string) (self::config()['username'] ?? '');
    }

    public static function password(): string
    {
        return (string) (self::config()['password'] ?? '');
    }

    public static function questionIndex(): string
    {
        return (string) (self::config()['index']['question'] ?? 'question_index');
    }
}
