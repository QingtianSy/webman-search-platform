<?php

namespace app\controller\admin;

use app\repository\mysql\QuestionSourceRepository;
use support\ApiResponse;
use support\Pagination;

class QuestionSourceController
{
    public function index()
    {
        $list = (new QuestionSourceRepository())->all();
        return ApiResponse::success(Pagination::format($list, count($list), 1, 20));
    }
}
