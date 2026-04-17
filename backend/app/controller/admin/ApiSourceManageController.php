<?php

namespace app\controller\admin;

class ApiSourceManageController
{
    public function index()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'id' => 1,
                        'name' => '本地健康检查接口源',
                        'code' => 'local_health',
                        'url' => 'http://127.0.0.1:8787/health',
                        'status' => 1
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
    }

    public function detail()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'id' => 1,
                'name' => '本地健康检查接口源',
                'code' => 'local_health',
                'url' => 'http://127.0.0.1:8787/health',
                'status' => 1
            ],
            'request_id' => ''
        ]);
    }

    public function test()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'tested' => true
            ],
            'request_id' => ''
        ]);
    }
}
