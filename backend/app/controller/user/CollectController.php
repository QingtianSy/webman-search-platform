<?php

namespace app\controller\user;

use app\common\CurrentUser;
use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\service\user\CollectService;
use app\validate\user\CollectValidate;
use support\ApiResponse;
use support\Request;

class CollectController
{
    public function accounts(Request $request)
    {
        $userId = CurrentUser::id($request);
        $query = UserQuery::parse($request->get());
        $list = (new CollectService())->accounts($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function tasks()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'task_no' => 'CT202604150001',
                        'status' => 2,
                        'course_count' => 1,
                        'question_count' => 2,
                        'runner_script' => 'pending://collect-core-script',
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
    }

    public function detail(Request $request)
    {
        $taskNo = (new CollectValidate())->taskNo($request->get());
        return ApiResponse::success((new CollectService())->detail($taskNo));
    }
}
