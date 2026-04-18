<?php

namespace app\common;

use app\service\auth\JwtService;
use support\Request;

class CurrentUser
{
    public static function id(Request $request): int
    {
        $authorization = (string) $request->header('Authorization', '');
        $token = trim(str_replace('Bearer', '', $authorization));
        $decoded = (new JwtService())->decode($token);
        return (int) (($decoded['payload']['uid'] ?? 0));
    }
}
