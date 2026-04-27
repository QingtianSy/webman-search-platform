<?php

namespace app\controller\admin;

use app\service\admin\MonitorService;
use support\ApiResponse;
use support\Request;

class MonitorController
{
    public function overview(Request $request)
    {
        return ApiResponse::success((new MonitorService())->overview());
    }

    /**
     * 读取最近 N 行 runtime/logs/app.log。
     * - level: ERROR/WARN/INFO 过滤（大小写不敏感）
     * - limit: 默认 200，最大 1000
     * - keyword: 对 message 做子串匹配
     * 日志量大时避免一次性加载整文件：直接 tail 读末尾 512KB 做行分割。
     */
    public function logs(Request $request)
    {
        $level = strtoupper(trim((string) $request->get('level', '')));
        $keyword = trim((string) $request->get('keyword', ''));
        $limit = (int) $request->get('limit', 200);
        $limit = max(1, min(1000, $limit));

        $path = function_exists('runtime_path')
            ? runtime_path() . '/logs/app.log'
            : (defined('BASE_PATH') ? BASE_PATH . '/runtime/logs/app.log' : '');

        if ($path === '' || !is_file($path)) {
            return ApiResponse::success([], '暂无日志');
        }

        $size = filesize($path);
        $readBytes = 512 * 1024;
        $offset = max(0, $size - $readBytes);

        $fp = @fopen($path, 'r');
        if (!$fp) {
            return ApiResponse::error(50001, '日志文件不可读');
        }
        fseek($fp, $offset);
        $chunk = stream_get_contents($fp);
        fclose($fp);
        if ($chunk === false) {
            return ApiResponse::error(50001, '日志读取失败');
        }
        // 从中间截的可能半行，丢掉第一行
        if ($offset > 0) {
            $pos = strpos($chunk, "\n");
            if ($pos !== false) {
                $chunk = substr($chunk, $pos + 1);
            }
        }

        $lines = preg_split('/\r?\n/', trim($chunk));
        $entries = [];
        // 倒序遍历：先拿到最新的
        foreach (array_reverse($lines) as $line) {
            if ($line === '') continue;
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\s+(.*)$/', $line, $m)) {
                continue;
            }
            $lv = strtoupper($m[2]);
            if ($level !== '' && $lv !== $level) continue;
            if ($keyword !== '' && stripos($m[3], $keyword) === false) continue;
            $entries[] = [
                'timestamp' => $m[1],
                'level' => $lv,
                'message' => $m[3],
                'channel' => 'app',
            ];
            if (count($entries) >= $limit) break;
        }

        return ApiResponse::success($entries, '日志读取成功');
    }
}
