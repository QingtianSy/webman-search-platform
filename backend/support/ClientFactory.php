<?php

namespace support;

class ClientFactory
{
    public static function mysql(): array
    {
        return config('database.connections.mysql', []);
    }

    public static function redis(): array
    {
        return config('redis.default', []);
    }

    public static function mongo(): array
    {
        return config('mongodb', []);
    }

    public static function elasticsearch(): array
    {
        return config('elasticsearch', []);
    }
}
