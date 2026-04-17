<?php

namespace app\controller\admin;

use app\repository\mysql\QuestionCategoryRepository;
use support\ApiResponse;
use support\Pagination;

class QuestionCategoryController
{
    public function index()
    {
        $list = (new QuestionCategoryRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
