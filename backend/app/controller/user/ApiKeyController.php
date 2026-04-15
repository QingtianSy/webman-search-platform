<?php

namespace app\controller\user;

use app\service\open\ApiKeyService;
use support\ApiResponse;

class ApiKeyController
{
    public function index(): array
    {
        $service = new ApiKeyService();
        return ApiResponse::success([
            'list' => $service->listByUserId(1),
            'total' => count($service->listByUserId(1)),
            'page' => 1,
            'page_size' => 20,
        ]);
    }
}
