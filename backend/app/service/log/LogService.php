<?php

namespace app\service\log;

class LogService
{
    public function info(string $message, array $context = []): bool
    {
        return true;
    }

    public function error(string $message, array $context = []): bool
    {
        return true;
    }
}
