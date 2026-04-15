<?php

namespace app\controller\admin;

use support\ApiResponse;

class QuestionController
{
    public function index(): array
    {
        return ApiResponse::success([
            'list' => [],
            'total' => 0,
            'page' => 1,
            'page_size' => 20,
        ], '题目列表接口骨架已创建');
    }

    public function create(): array
    {
        return ApiResponse::success([], '题目新增接口骨架已创建');
    }
}
