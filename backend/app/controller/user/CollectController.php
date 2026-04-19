<?php

namespace app\controller\user;

use app\common\user\UserListBuilder;
use app\common\user\UserQuery;
use app\repository\mysql\OperateLogRepository;
use app\service\user\CollectService;
use app\validate\user\CollectValidate;
use support\ApiResponse;
use support\Request;

class CollectController
{
    public function accounts(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        $list = (new CollectService())->accounts($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function tasks(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        $list = (new CollectService())->tasks($userId);
        return ApiResponse::success(UserListBuilder::make($list, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $taskNo = (new CollectValidate())->taskNo($request->get());
        return ApiResponse::success((new CollectService())->detail($userId, $taskNo));
    }

    public function queryCourses(Request $request)
    {
        $data = (new CollectValidate())->queryCourses($request->post());
        return ApiResponse::success(
            (new CollectService())->queryCourses($data['account'], $data['password']),
            '查询成功'
        );
    }

    public function submitCollect(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new CollectValidate())->submitCollect($request->post());
        $result = (new CollectService())->submitCollect($userId, $data);
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'collect', 'action' => 'submit', 'content' => "提交采集任务: {$data['route']}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, '采集任务已提交');
    }
}
