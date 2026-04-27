<?php

namespace app\controller\admin;

use app\common\admin\AdminQuery;
use app\exception\BusinessException;
use support\ApiResponse;
use support\Db;
use support\Request;

// 管理端跨用户日志查询：4 种日志（balance/payment/operate/login）共用同一套筛选协议：
//   user_id / username / type(仅 balance 有意义) / date_from / date_to / page / page_size
// 所有返回体统一为 {list, total, page, page_size}，方便前端 log-filter-card.vue 复用。
//
// DB 故障统一抛 BusinessException(50001)；参数非法返 40001。不做"空列表兜底"。
class LogController
{
    // 余额变动日志：来自 balance_logs；type 支持 recharge/consume/refund 精确匹配。
    public function balance(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        $page = $query['page'];
        $pageSize = $query['page_size'];
        $type = trim((string) $request->get('type', ''));
        $userId = (int) $request->get('user_id', 0);
        $username = trim((string) $request->get('username', ''));

        try {
            $builder = Db::table('balance_logs as b')
                ->leftJoin('users as u', 'u.id', '=', 'b.user_id');
            $this->applyUserFilter($builder, $userId, $username);
            $this->applyTimeRange($builder, 'b.created_at', $query['start_time'], $query['end_time']);
            if ($type !== '') {
                $builder->where('b.type', $type);
            }

            $total = (clone $builder)->count();
            $list = $builder->select([
                'b.id', 'b.user_id', 'u.username', 'b.type',
                'b.amount', 'b.balance_after', 'b.remark', 'b.created_at',
            ])->orderByDesc('b.id')->forPage($page, $pageSize)->get()->toArray();
        } catch (\Throwable $e) {
            error_log('[admin LogController] balance failed: ' . $e->getMessage());
            throw new BusinessException('余额日志服务暂不可用', 50001);
        }
        return ApiResponse::success([
            'list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize,
        ]);
    }

    // 支付日志：直接用订单表 order 作为权威口径（payment_logs 是补充审计）。
    // status：0 待支付 / 1 已支付 / 2 已取消或过期。
    public function payment(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        $page = $query['page'];
        $pageSize = $query['page_size'];
        $userId = (int) $request->get('user_id', 0);
        $username = trim((string) $request->get('username', ''));
        $status = $request->get('status', null);
        $payType = trim((string) $request->get('pay_type', ''));

        try {
            $builder = Db::table('order as o')
                ->leftJoin('users as u', 'u.id', '=', 'o.user_id');
            $this->applyUserFilter($builder, $userId, $username);
            $this->applyTimeRange($builder, 'o.created_at', $query['start_time'], $query['end_time']);
            if ($status !== null && $status !== '') {
                $builder->where('o.status', (int) $status);
            }
            if ($payType !== '') {
                $builder->where('o.pay_type', $payType);
            }
            $total = (clone $builder)->count();
            $list = $builder->select([
                'o.id', 'o.order_no', 'o.trade_no', 'o.user_id', 'u.username',
                'o.type', 'o.plan_id', 'o.amount', 'o.pay_type',
                'o.status', 'o.created_at', 'o.paid_at',
            ])->orderByDesc('o.id')->forPage($page, $pageSize)->get()->toArray();
        } catch (\Throwable $e) {
            error_log('[admin LogController] payment failed: ' . $e->getMessage());
            throw new BusinessException('支付日志服务暂不可用', 50001);
        }
        return ApiResponse::success([
            'list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize,
        ]);
    }

    // 操作日志：module / action 是常见过滤维度。
    public function operate(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        $page = $query['page'];
        $pageSize = $query['page_size'];
        $userId = (int) $request->get('user_id', 0);
        $username = trim((string) $request->get('username', ''));
        $module = trim((string) $request->get('module', ''));
        $action = trim((string) $request->get('action', ''));

        try {
            $builder = Db::table('operate_logs as o')
                ->leftJoin('users as u', 'u.id', '=', 'o.user_id');
            $this->applyUserFilter($builder, $userId, $username);
            $this->applyTimeRange($builder, 'o.created_at', $query['start_time'], $query['end_time']);
            if ($module !== '') $builder->where('o.module', $module);
            if ($action !== '') $builder->where('o.action', $action);

            $total = (clone $builder)->count();
            $list = $builder->select([
                'o.id', 'o.user_id', 'u.username', 'o.module',
                'o.action', 'o.content', 'o.ip', 'o.created_at',
            ])->orderByDesc('o.id')->forPage($page, $pageSize)->get()->toArray();
        } catch (\Throwable $e) {
            error_log('[admin LogController] operate failed: ' . $e->getMessage());
            throw new BusinessException('操作日志服务暂不可用', 50001);
        }
        return ApiResponse::success([
            'list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize,
        ]);
    }

    // 登录日志：status=1 成功 / 0 失败（失败登录 user_id=0）。
    public function login(Request $request)
    {
        $query = AdminQuery::parse($request->get());
        $page = $query['page'];
        $pageSize = $query['page_size'];
        $userId = (int) $request->get('user_id', 0);
        $username = trim((string) $request->get('username', ''));
        $status = $request->get('status', null);
        $ip = trim((string) $request->get('ip', ''));

        try {
            $builder = Db::table('login_logs as l')
                ->leftJoin('users as u', 'u.id', '=', 'l.user_id');
            $this->applyUserFilter($builder, $userId, $username);
            $this->applyTimeRange($builder, 'l.created_at', $query['start_time'], $query['end_time']);
            if ($status !== null && $status !== '') {
                $builder->where('l.status', (int) $status);
            }
            if ($ip !== '') {
                $builder->where('l.ip', $ip);
            }

            $total = (clone $builder)->count();
            $list = $builder->select([
                'l.id', 'l.user_id', 'u.username', 'l.ip',
                'l.user_agent', 'l.status', 'l.created_at',
            ])->orderByDesc('l.id')->forPage($page, $pageSize)->get()->toArray();
        } catch (\Throwable $e) {
            error_log('[admin LogController] login failed: ' . $e->getMessage());
            throw new BusinessException('登录日志服务暂不可用', 50001);
        }
        return ApiResponse::success([
            'list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize,
        ]);
    }

    // user_id 命中精确；username 走 LIKE，给 admin 端筛选框用户名片段查询。
    // 两者优先级：user_id > username（user_id 非 0 时忽略 username）。
    protected function applyUserFilter($builder, int $userId, string $username): void
    {
        if ($userId > 0) {
            $builder->where('u.id', $userId);
            return;
        }
        if ($username !== '') {
            $builder->where('u.username', 'like', '%' . $username . '%');
        }
    }

    protected function applyTimeRange($builder, string $col, ?string $start, ?string $end): void
    {
        if (!empty($start)) {
            $builder->where($col, '>=', $start);
        }
        if (!empty($end)) {
            $builder->where($col, '<=', $end);
        }
    }
}
