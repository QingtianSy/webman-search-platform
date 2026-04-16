<?php

namespace app\controller\admin;

use app\repository\mysql\ApiSourceRepository;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class ApiSourceManageController
{
    public function index(): array
    {
        $list = (new ApiSourceRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }

    public function detail(Request $request): array
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiSourceRepository())->findById($id));
    }

    public function test(Request $request): array
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success((new ApiSourceRepository())->test($id));
    }
}
