<?php

namespace app\controller\user;

use app\exception\BusinessException;
use app\service\user\ApiSourceService;
use app\validate\user\ApiSourceValidate;
use support\ApiResponse;
use support\Db;
use support\Request;

class ApiSourceController
{
    public function index(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $query = $request->get();
        return ApiResponse::success((new ApiSourceService())->getList($userId, $query));
    }

    public function detail(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->get());
        $row = (new ApiSourceService())->detail($userId, $id);
        if (empty($row)) {
            return ApiResponse::error(40004, '接口源不存在');
        }
        return ApiResponse::success($row);
    }

    public function create(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiSourceValidate())->create($request->post());
        // service 层在故障/记录不存在两种情况会分别抛 50001 / 40004 BusinessException，
        // 这里仅负责返回 success。不再做 `empty => 50000` 笼统兜底，避免覆盖 service 的精确状态码。
        $result = (new ApiSourceService())->create($userId, $data);
        return ApiResponse::success($result, '接口源创建成功');
    }

    public function update(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $data = (new ApiSourceValidate())->update($request->post());
        $id = $data['id'];
        unset($data['id']);
        $result = (new ApiSourceService())->update($userId, $id, $data);
        return ApiResponse::success($result, '接口源更新成功');
    }

    public function delete(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->get());
        (new ApiSourceService())->delete($userId, $id);
        return ApiResponse::success(['id' => $id], '接口源删除成功');
    }

    public function test(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->post());
        $result = (new ApiSourceService())->test($userId, $id);
        if (($result['status'] ?? '') !== 'success') {
            return ApiResponse::error(50000, $result['message'] ?? '接口源测试失败', $result);
        }
        return ApiResponse::success($result, '接口源测试完成');
    }

    // 异步测试：投递后立即返回 task_id，前端走 testResult 轮询。
    public function testSubmit(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (new ApiSourceValidate())->id($request->post());
        return ApiResponse::success(
            (new ApiSourceService())->submitTest($userId, $id),
            '接口源测试已提交'
        );
    }

    public function testResult(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $taskId = (string) $request->get('task_id', '');
        if ($taskId === '') {
            return ApiResponse::error(40001, '缺少 task_id');
        }
        return ApiResponse::success((new ApiSourceService())->getTestResult($userId, $taskId));
    }

    // 启禁切换：0↔1 翻转；行归属校验不通过 → 40004。
    public function toggle(Request $request)
    {
        $userId = (int) ($request->userId ?? 0);
        $id = (int) $request->post('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '接口源 ID 不能为空');
        }
        try {
            $row = Db::table('user_api_sources')->where('id', $id)->where('user_id', $userId)->first();
            if (!$row) {
                return ApiResponse::error(40004, '接口源不存在');
            }
            $next = ((int) $row->status) === 1 ? 0 : 1;
            Db::table('user_api_sources')->where('id', $id)->update([
                'status' => $next,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return ApiResponse::success(['id' => $id, 'status' => $next], $next === 1 ? '已启用' : '已禁用');
        } catch (\Throwable $e) {
            error_log('[ApiSourceController] toggle failed: ' . $e->getMessage());
            throw new BusinessException('接口源服务暂不可用，请稍后重试', 50001);
        }
    }
}
