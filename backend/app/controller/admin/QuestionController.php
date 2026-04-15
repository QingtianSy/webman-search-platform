<?php

namespace app\controller\admin;

use app\service\question\QuestionService;
use support\ApiResponse;
use support\Request;

class QuestionController
{
    public function index(?Request $request = null): array
    {
        $request ??= new Request();
        $filters = [
            'stem' => (string) $request->input('stem', ''),
        ];
        $service = new QuestionService();
        return ApiResponse::success($service->getList($filters), '题目列表接口骨架已接入服务层');
    }

    public function create(): array
    {
        return ApiResponse::success([], '题目新增接口骨架已创建');
    }
}
