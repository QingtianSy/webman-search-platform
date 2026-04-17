<?php

namespace app\controller\admin;

use app\repository\mysql\QuestionTypeRepository;
use support\ApiResponse;
use support\Pagination;

class QuestionTypeController
{
    public function index()
    {
        $list = (new QuestionTypeRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
