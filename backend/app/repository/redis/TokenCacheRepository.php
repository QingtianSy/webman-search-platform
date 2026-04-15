<?php

namespace app\repository\redis;

class TokenCacheRepository
{
    public function setUserToken(int $userId, string $token, int $ttl = 604800): bool
    {
        return true;
    }
}
