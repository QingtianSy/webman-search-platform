<?php

namespace app\controller\user;

use app\common\user\UserQuery;
use app\exception\BusinessException;
use app\repository\mysql\CollectAccountRepository;
use app\repository\mysql\CollectTaskRepository;
use app\repository\mysql\OperateLogRepository;
use app\service\user\CollectService;
use app\validate\user\CollectValidate;
use support\ApiResponse;
use support\Pagination;
use support\Request;

class CollectController
{
    public function accounts(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        try {
            $repo = new CollectAccountRepository();
            $total = $repo->countByUserIdStrict($userId);
            $list = $repo->findPageByUserIdStrict($userId, $query['page'], $query['page_size']);
        } catch (\RuntimeException $e) {
            // 之前返回 0/[]，前端以为"没账号"，可能引导用户重新添加已存在的账号；改为 50001 暴露故障。
            throw new BusinessException('采集账号列表暂不可用，请稍后重试', 50001);
        }
        return ApiResponse::success(Pagination::format($list, $total, $query['page'], $query['page_size']));
    }

    public function tasks(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = UserQuery::parse($request->get());
        try {
            $repo = new CollectTaskRepository();
            $total = $repo->countAllStrict($userId);
            $list = $repo->findPageStrict($query['page'], $query['page_size'], $userId);
        } catch (\RuntimeException $e) {
            throw new BusinessException('采集任务列表暂不可用，请稍后重试', 50001);
        }
        return ApiResponse::success(Pagination::format($list, $total, $query['page'], $query['page_size']));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $taskNo = (new CollectValidate())->taskNo($request->get());
        $result = (new CollectService())->detail($userId, $taskNo);
        if (empty($result)) {
            return ApiResponse::error(40004, '采集任务不存在');
        }
        return ApiResponse::success($result);
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
        (new OperateLogRepository())->create(['user_id' => $userId, 'module' => 'collect', 'action' => 'submit', 'content' => "提交采集任务: {$data['collect_type']}", 'ip' => $request->getRealIp()]);
        return ApiResponse::success($result, '采集任务已提交');
    }
}
