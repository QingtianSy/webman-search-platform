<?php

namespace app\middleware;

use app\common\RequestId;

class RequestIdMiddleware
{
    public function process(mixed $request, callable $handler): mixed
    {
        $requestId = RequestId::generate();
        if (is_object($request)) {
            $request->requestId = $requestId;
        }
        return $handler($request);
    }
}
