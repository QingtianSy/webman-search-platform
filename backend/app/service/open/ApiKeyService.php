<?php

namespace app\service\open;

class ApiKeyService
{
    public function verify(?string $apiKey, ?string $apiSecret): bool
    {
        return !empty($apiKey) && !empty($apiSecret);
    }
}
