<?php

namespace app\controller\admin;

use app\repository\mysql\QuestionCategoryRepository;
use app\repository\mysql\QuestionSourceRepository;
use app\repository\mysql\QuestionTagRepository;
use app\repository\mysql\QuestionTypeRepository;
use app\service\question\QuestionService;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class QuestionController
{
    public function index(Request $request)
    {
                $filters = [
            'stem' => (string) $request->input('stem', ''),
        ];
        $service = new QuestionService();
        return ApiResponse::success($service->getList($filters), '题目列表接口骨架已接入服务层');
    }

    public function detail(Request $request)
    {
                $id = (int) $request->input('id', 0);
        return ApiResponse::success((new QuestionService())->detail($id));
    }

    public function create()
    {
        return ApiResponse::success([], '题目新增接口骨架已创建');
    }

    public function update(Request $request)
    {
                $id = (int) $request->input('id', 0);
        $stem = (string) $request->input('stem', '');
        $updated = (new \app\repository\mongo\QuestionRepository())->update($id, ['stem' => $stem]);
        return ApiResponse::success($updated, '题目更新骨架已创建');
    }

    public function delete(Request $request)
    {
                $id = (int) $request->input('id', 0);
        $deleted = (new \app\repository\mongo\QuestionRepository())->delete($id);
        return ApiResponse::success(['deleted' => $deleted], '题目删除骨架已创建');
    }
}
