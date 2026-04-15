<?php

namespace app\repository\redis;

class RateLimitRepository
{
    public function hit(string $key, int $ttl = 60): int
    {
        return 1;
    }
}
