<?php

namespace app\middleware;

use app\common\RequestId;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $requestId = RequestId::generate();
        $request->requestId = $requestId;
        /** @var Response $response */
        $response = $handler($request);
        $response->withHeader('X-Request-Id', $requestId);
        return $response;
    }
}
