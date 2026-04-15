<?php

namespace app\controller\admin;

use support\ApiResponse;
use support\Request;

class QuestionController
{
    public function index(?Request $request = null): array
    {
        $request ??= new Request();
        return ApiResponse::success([
            'list' => [
                [
                    'question_id' => 100001,
                    'stem' => (string) $request->input('stem', '示例题目'),
                    'answer_text' => 'A',
                    'type_name' => '单选题',
                    'source_name' => '本地题库',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ],
            'total' => 1,
            'page' => 1,
            'page_size' => 20,
        ], '题目列表接口骨架已完善');
    }

    public function create(): array
    {
        return ApiResponse::success([], '题目新增接口骨架已创建');
    }
}
