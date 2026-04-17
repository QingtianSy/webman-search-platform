<?php

namespace app\controller\admin;

use app\repository\mysql\QuestionTagRepository;
use support\ApiResponse;
use support\Pagination;

class QuestionTagController
{
    public function index()
    {
        $list = (new QuestionTagRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
