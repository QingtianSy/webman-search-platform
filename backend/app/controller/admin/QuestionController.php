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
    public function index(?Request $request = null): array
    {
        $request ??= new Request();
        $filters = [
            'stem' => (string) $request->input('stem', ''),
        ];
        $service = new QuestionService();
        return ApiResponse::success($service->getList($filters), '题目列表接口骨架已接入服务层');
    }

    public function detail(?Request $request = null): array
    {
        $request ??= new Request();
        $id = (int) $request->input('id', 0);
        return ApiResponse::success((new QuestionService())->detail($id));
    }

    public function create(): array
    {
        return ApiResponse::success([], '题目新增接口骨架已创建');
    }
}

class QuestionCategoryController
{
    public function index(): array
    {
        $list = (new QuestionCategoryRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class QuestionTypeController
{
    public function index(): array
    {
        $list = (new QuestionTypeRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class QuestionSourceController
{
    public function index(): array
    {
        $list = (new QuestionSourceRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}

class QuestionTagController
{
    public function index(): array
    {
        $list = (new QuestionTagRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
