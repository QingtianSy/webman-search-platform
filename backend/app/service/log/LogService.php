<?php

namespace app\service\log;

class LogService
{
    public function info(string $message, array $context = []): bool
    {
        $line = date('Y-m-d H:i:s') . ' [INFO] ' . $message;
        if ($context) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        error_log($line);
        return true;
    }

    public function error(string $message, array $context = []): bool
    {
        $line = date('Y-m-d H:i:s') . ' [ERROR] ' . $message;
        if ($context) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        error_log($line);
        return true;
    }
}
