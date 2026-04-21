<?php

namespace app\process;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;
use app\repository\mysql\CollectTaskRepository;
use app\repository\mysql\SystemConfigRepository;
use Workerman\Timer;

class CollectWorker
{
    protected CollectTaskRepository $taskRepo;
    protected QuestionRepository $questionRepo;
    protected QuestionIndexRepository $esRepo;
    protected array $runningTasks = [];
    protected array $collectConfig = [];
    protected bool $recoveryDone = false;

    public function onWorkerStart(): void
    {
        $this->taskRepo = new CollectTaskRepository();
        $this->questionRepo = new QuestionRepository();
        $this->esRepo = new QuestionIndexRepository();
        $this->loadCollectConfig();

        $this->recoverRunningTasks();
        Timer::add(5, [$this, 'poll']);
        Timer::add(300, [$this, 'loadCollectConfig']);
    }

    public function loadCollectConfig(): void
    {
        // 配置读取走 Strict：之前用非严格 getByGroup，DB 故障 → 返 [] → 所有 getConfig('xxx', default) 回落默认值继续跑，
        // concurrency/timeout/separator 等全被静默重置，管理员在后台看到的"当前配置"与 worker 实际使用配置可能不一致。
        // 读失败时保留上一次成功的快照而非清空，避免立刻退化到硬编码默认；只记日志让运维知道刷新失败。
        try {
            $rows = (new SystemConfigRepository())->getByGroupStrict('collect');
        } catch (\RuntimeException $e) {
            error_log("[CollectWorker] loadCollectConfig failed, keep previous snapshot: " . $e->getMessage());
            return;
        }
        $map = [];
        foreach ($rows as $row) {
            $map[$row['config_key']] = $row['config_value'];
        }
        $this->collectConfig = $map;
    }

    protected function getConfig(string $key, string $default = ''): string
    {
        return $this->collectConfig[$key] ?? $default;
    }

    protected function recoverRunningTasks(): void
    {
        // Strict：worker 启动或刚故障恢复时 MySQL 抖一下，非严格 findByStatus 返 [] 会让 status=1 的旧任务永远卡住。
        // 认领失败时不标记 recoveryDone，下一次 poll() 继续尝试，直到成功为止。
        try {
            $tasks = $this->taskRepo->findByStatusStrict(1);
        } catch (\RuntimeException $e) {
            error_log("[CollectWorker] recoverRunningTasks deferred, DB unavailable: " . $e->getMessage());
            return;
        }
        $recoveryOk = true;
        foreach ($tasks as $task) {
            $taskNo = $task['task_no'] ?? '';
            $pid = (int) ($task['runner_script'] ?? 0);
            if ($taskNo === '' || $pid <= 0) {
                if ($taskNo !== '') {
                    $recoveredPid = $this->tryRecoverPid($taskNo);
                    if ($recoveredPid > 0) {
                        $this->taskRepo->updateRunnerPid($taskNo, $recoveredPid);
                        $this->runningTasks[$taskNo] = ['pid' => $recoveredPid, 'started_at' => time()];
                        error_log("[CollectWorker] adopted orphan task={$taskNo} pid={$recoveredPid}");
                    } else {
                        // Strict：orphan 回退用非严格 updateStatus 时，DB 抖动 → rowCount=0 → 仅日志一行 "reset ... to pending"，
                        // 然而任务在库里还是 status=1，下一轮 poll 因 recoveryDone=true 不会再扫。改用 strict 变体，
                        // 失败时保持 recoveryDone=false，等下个周期重试这一笔，同时不推进处理该任务。
                        try {
                            $this->taskRepo->updateStatusStrict($taskNo, 0, '进程丢失，重新排队');
                            $this->cleanupPidFile($taskNo);
                            error_log("[CollectWorker] reset orphaned task={$taskNo} to pending");
                        } catch (\RuntimeException $e) {
                            $recoveryOk = false;
                            error_log("[CollectWorker] reset orphan failed task={$taskNo}, will retry: " . $e->getMessage());
                        }
                    }
                }
                continue;
            }
            // 重启后可能已存在，避免重复覆盖 started_at
            if (!isset($this->runningTasks[$taskNo])) {
                $this->runningTasks[$taskNo] = [
                    'pid' => $pid,
                    'started_at' => time(),
                ];
                error_log("[CollectWorker] recovered task={$taskNo} pid={$pid}");
            }
        }
        $this->recoveryDone = $recoveryOk;
    }

    public function poll(): void
    {
        // 启动认领失败时每个轮询周期重试一次，直到成功。
        if (!$this->recoveryDone) {
            $this->recoverRunningTasks();
        }
        $this->checkRunningTasks();
        $this->claimPendingTasks();
    }

    protected function claimPendingTasks(): void
    {
        $task = $this->taskRepo->claimPending();
        if (!$task) {
            return;
        }

        $taskNo = $task['task_no'];
        $pid = $this->startPython($task);
        if ($pid <= 0) {
            $this->taskRepo->updateStatus($taskNo, 3, 'Python 进程启动失败');
            return;
        }

        $this->taskRepo->updateRunnerPid($taskNo, $pid);
        $this->runningTasks[$taskNo] = [
            'pid' => $pid,
            'started_at' => time(),
        ];
        error_log("[CollectWorker] started task={$taskNo} pid={$pid}");
    }

    protected function startPython(array $task): int
    {
        $taskNo = $task['task_no'];
        $phone = $task['account_phone'] ?? '';
        $password = $task['account_password'] ?? '';
        $courseIds = $task['course_ids'] ?? '';
        $mode = $task['collect_type'] ?? 'courses';
        $proxyUrl = $task['proxy_url'] ?? '';

        $concurrency = $this->getConfig('collect_concurrency', '1');
        $courseConcurrency = $this->getConfig('collect_course_concurrency', '1');
        $requestIntervalMs = $this->getConfig('collect_request_interval_ms', '120');
        $separator = $this->getConfig('collect_separator', '###');
        $outputMode = $this->getConfig('collect_output_mode', 'json');
        $progressInterval = $this->getConfig('collect_progress_interval', '10');

        $projectRoot = dirname(__DIR__, 3);
        $pythonDir = $projectRoot . '/xxt';
        $resultsDir = $pythonDir . '/results';
        if (!is_dir($resultsDir)) {
            @mkdir($resultsDir, 0755, true);
        }

        $pidFile = '/tmp/collect_' . $taskNo . '.pid';
        $logFile = $resultsDir . '/' . $taskNo . '.log';

        $cmd = sprintf(
            'cd %s && nohup python3 run.py --account %s --mode %s --output %s --task-no %s --concurrency %s --course-concurrency %s --request-interval-ms %s --separator %s',
            escapeshellarg($pythonDir),
            escapeshellarg($phone . '----' . $password),
            escapeshellarg($mode),
            escapeshellarg($outputMode),
            escapeshellarg($taskNo),
            escapeshellarg($concurrency),
            escapeshellarg($courseConcurrency),
            escapeshellarg($requestIntervalMs),
            escapeshellarg($separator)
        );

        if ($courseIds !== '') {
            $cmd .= ' --course-ids ' . escapeshellarg($courseIds);
        }

        if ($proxyUrl !== '') {
            $cmd .= ' --proxy ' . escapeshellarg($proxyUrl);
        }

        $cmd .= sprintf(' > %s 2>&1 & echo $! > %s',
            escapeshellarg($logFile),
            escapeshellarg($pidFile)
        );

        $output = [];
        @exec($cmd, $output);
        usleep(500000);

        if (!is_file($pidFile)) {
            return 0;
        }
        $pid = (int) trim((string) file_get_contents($pidFile));
        return $pid;
    }

    protected function checkRunningTasks(): void
    {
        foreach ($this->runningTasks as $taskNo => $info) {
            $pid = $info['pid'];
            $isRunning = $this->isProcessRunning($pid);

            if ($isRunning) {
                $timeoutSeconds = (int) $this->getConfig('collect_timeout_seconds', '7200');
                if (time() - $info['started_at'] > $timeoutSeconds) {
                    $this->killProcess($pid);
                    $this->taskRepo->updateStatus($taskNo, 3, "采集超时({$timeoutSeconds}s)");
                    unset($this->runningTasks[$taskNo]);
                    $this->cleanupPidFile($taskNo);
                    error_log("[CollectWorker] timeout task={$taskNo} pid={$pid}");
                }
                continue;
            }

            unset($this->runningTasks[$taskNo]);
            $this->cleanupPidFile($taskNo);
            error_log("[CollectWorker] python finished task={$taskNo} pid={$pid}");
            // 先回库确认任务状态：管理员在 Worker 检测到进程退出之前调用 stop()（CollectAdminService::stop）
            // 会把 status 置为 4（手动停止）。此时不能再走 importResults() 覆盖状态、写入数据，否则管理员的"停止"结果
            // 会被 status=2/3/4 正常收尾覆盖，而且可能把中途的脏数据导入库。
            // Strict：之前用 findByTaskNo，DB 故障 → null → 误判"任务不存在，继续 import"，
            // 可能把已手动停止的任务重新导入并改写状态。改为 Strict 后 DB 故障直接跳过 import，等下一轮 poll 再确认。
            try {
                $current = $this->taskRepo->findByTaskNoStrict($taskNo);
            } catch (\RuntimeException $e) {
                error_log("[CollectWorker] skip import task={$taskNo}, status check failed: " . $e->getMessage());
                continue;
            }
            if ($current && (int) ($current['status'] ?? -1) !== 1) {
                error_log("[CollectWorker] skip import task={$taskNo} status={$current['status']} error_message={$current['error_message']} (stopped/terminated externally)");
                continue;
            }
            $this->importResults($taskNo);
        }
    }

    protected function importResults(string $taskNo): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $jsonlFile = $projectRoot . '/xxt/results/' . $taskNo . '.jsonl';

        if (!is_file($jsonlFile)) {
            $this->taskRepo->updateStatus($taskNo, 3, '采集结果文件不存在');
            return;
        }

        try {
            $importResult = $this->questionRepo->importFromJsonl($jsonlFile, $taskNo);
            $imported = (int) ($importResult['imported'] ?? 0);
            $importFailed = (int) ($importResult['failed'] ?? 0);
            $importSkipped = (int) ($importResult['skipped'] ?? 0);
            $importDuplicated = (int) ($importResult['duplicated'] ?? 0);
            $esIndexed = 0;
            if ($imported > 0) {
                // 旧实现先 findByTaskNo 把整批题目（10W+）堆进数组再 array_chunk；这里改为迭代器流式消费。
                $batch = [];
                foreach ($this->questionRepo->findByTaskNoIterator($taskNo) as $row) {
                    $batch[] = $row;
                    if (count($batch) >= 2000) {
                        $esIndexed += $this->esRepo->bulkIndex($batch);
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    $esIndexed += $this->esRepo->bulkIndex($batch);
                }
            }
            $esFailed = $imported - $esIndexed;
            $this->taskRepo->updateProgress($taskNo, $imported, $esIndexed, $esFailed);
            $notes = [];
            if ($importFailed > 0) {
                $notes[] = "Mongo部分失败: {$importFailed}条";
            }
            if ($importSkipped > 0) {
                $notes[] = "跳过无效行: {$importSkipped}条";
            }
            if ($importDuplicated > 0) {
                // 唯一索引去重命中 = 预期行为，不算失败但需要让管理员知道实际落库量。
                $notes[] = "重复已跳过: {$importDuplicated}条";
            }
            if ($esFailed > 0) {
                $notes[] = "ES索引部分失败: {$esFailed}/{$imported}条未索引";
            }
            if (!empty($notes)) {
                $this->taskRepo->updateStatus($taskNo, 4, implode('; ', $notes));
            } else {
                $this->taskRepo->updateStatus($taskNo, 2);
            }
            $reasonsLog = '';
            if (!empty($importResult['failed_reasons'])) {
                $reasonsLog = ' import_reasons=' . json_encode(array_slice($importResult['failed_reasons'], 0, 3), JSON_UNESCAPED_UNICODE);
            }
            error_log("[CollectWorker] imported task={$taskNo} mongo={$imported} mongo_dup={$importDuplicated} mongo_fail={$importFailed} mongo_skip={$importSkipped} es_ok={$esIndexed} es_fail={$esFailed}{$reasonsLog}");
        } catch (\Throwable $e) {
            $this->taskRepo->updateStatus($taskNo, 3, '导入失败: ' . $e->getMessage());
            error_log("[CollectWorker] import failed task={$taskNo}: " . $e->getMessage());
        }
    }

    protected function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }
        $output = [];
        @exec("kill -0 {$pid} 2>/dev/null; echo \$?", $output);
        if (!isset($output[0]) || trim($output[0]) !== '0') {
            return false;
        }
        $cmdline = [];
        @exec("ps -p {$pid} -o args= 2>/dev/null", $cmdline);
        $cmd = trim($cmdline[0] ?? '');
        return $cmd !== '' && (str_contains($cmd, 'run.py') || str_contains($cmd, 'collect'));
    }

    protected function killProcess(int $pid): void
    {
        if ($pid <= 0) {
            return;
        }
        $cmdline = [];
        @exec("ps -p {$pid} -o args= 2>/dev/null", $cmdline);
        $cmd = trim($cmdline[0] ?? '');
        if ($cmd === '' || (!str_contains($cmd, 'run.py') && !str_contains($cmd, 'collect'))) {
            error_log("[CollectWorker] skip kill pid={$pid}, not a collect process: {$cmd}");
            return;
        }
        @exec("pkill -P {$pid} 2>/dev/null");
        @exec("kill {$pid} 2>/dev/null");
    }

    protected function tryRecoverPid(string $taskNo): int
    {
        $pidFile = '/tmp/collect_' . $taskNo . '.pid';
        if (is_file($pidFile)) {
            $pid = (int) trim((string) file_get_contents($pidFile));
            if ($pid > 0 && $this->isProcessRunning($pid)) {
                return $pid;
            }
        }
        $output = [];
        @exec("pgrep -f " . escapeshellarg("task-no {$taskNo}") . " 2>/dev/null", $output);
        foreach ($output as $line) {
            $pid = (int) trim($line);
            if ($pid > 0) {
                return $pid;
            }
        }
        return 0;
    }

    protected function cleanupPidFile(string $taskNo): void
    {
        $pidFile = '/tmp/collect_' . $taskNo . '.pid';
        if (is_file($pidFile)) {
            @unlink($pidFile);
        }
    }
}
