<?php

namespace app\controller\admin;

class SystemConfigController
{
    public function index()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'list' => [
                    [
                        'config_group' => 'system',
                        'config_key' => 'site_name',
                        'config_value' => '搜题平台'
                    ]
                ],
                'total' => 1,
                'page' => 1,
                'page_size' => 20
            ],
            'request_id' => ''
        ]);
    }

    public function update()
    {
        return json([
            'code' => 1,
            'msg' => 'success',
            'data' => ['updated' => true],
            'request_id' => ''
        ]);
    }
}
