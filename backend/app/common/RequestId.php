<?php

namespace app\common;

class RequestId
{
    public static function generate(): string
    {
        return date('YmdHis') . bin2hex(random_bytes(4));
    }
}
