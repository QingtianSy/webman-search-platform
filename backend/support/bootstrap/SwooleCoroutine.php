<?php

namespace support\bootstrap;

use Webman\Bootstrap;
use Workerman\Worker;

class SwooleCoroutine implements Bootstrap
{
    public static function start(?Worker $worker): void
    {
        if (!extension_loaded('swoole')) {
            return;
        }
        \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
    }
}
