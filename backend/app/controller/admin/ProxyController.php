<?php

namespace app\controller\admin;

use app\service\proxy\ProxyService;
use support\ApiResponse;
use support\Request;

class ProxyController
{
    public function list(Request $request)
    {
        return ApiResponse::success((new ProxyService())->list($request->get()));
    }

    public function detail(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '参数错误');
        }
        // 服务层 DB 故障会抛 BusinessException(50001)；空数组只可能是真"不存在"。
        $row = (new ProxyService())->detail($id);
        if (empty($row)) {
            return ApiResponse::error(40004, '代理不存在');
        }
        return ApiResponse::success($row);
    }

    public function create(Request $request)
    {
        $data = $request->post();
        if (empty($data['protocol']) || empty($data['host']) || empty($data['port'])) {
            return ApiResponse::error(40001, '协议、地址、端口不能为空');
        }
        // service 在 DB 故障/创建失败时抛 BusinessException，全局异常处理器统一返回。
        $result = (new ProxyService())->create($data);
        return ApiResponse::success($result);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '参数错误');
        }
        return ApiResponse::success((new ProxyService())->update($id, $request->post()));
    }

    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '参数错误');
        }
        return ApiResponse::success((new ProxyService())->delete($id));
    }

    public function probe(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return ApiResponse::error(40001, '参数错误');
        }
        $result = (new ProxyService())->probe($id);
        if (!($result['success'] ?? false)) {
            return ApiResponse::error(50000, $result['msg'] ?? '探测失败', $result);
        }
        return ApiResponse::success($result);
    }

    public function quickAdd(Request $request)
    {
        $raw = $request->input('raw', '');
        if ($raw === '') {
            return ApiResponse::error(40001, '请输入代理地址');
        }
        return ApiResponse::success((new ProxyService())->quickAdd($raw));
    }

    public function batchImport(Request $request)
    {
        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return ApiResponse::error(40001, '导入数据为空');
        }
        return ApiResponse::success((new ProxyService())->batchImport($items));
    }

    public function batchExport(Request $request)
    {
        return ApiResponse::success((new ProxyService())->batchExport());
    }

    public function probeAll(Request $request)
    {
        return ApiResponse::success((new ProxyService())->probeAll());
    }
}
