<?php

namespace app\controller\user;

use app\common\user\UserQuery;
use app\service\user\DocService;
use app\validate\user\DocValidate;
use support\ApiResponse;
use support\Request;

class DocController
{
    public function categories(Request $request)
    {
        $query = UserQuery::parse($request->all());
        return ApiResponse::success((new DocService())->categories($query));
    }

    public function detail(Request $request)
    {
        $slug = (new DocValidate())->slug($request->all());
        return ApiResponse::success((new DocService())->detail($slug));
    }

    public function config()
    {
        return ApiResponse::success((new DocService())->config());
    }
}
