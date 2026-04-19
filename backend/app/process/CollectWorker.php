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
        $rows = (new SystemConfigRepository())->getByGroup('collect');
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
        $tasks = $this->taskRepo->findByStatus(1);
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
                        $this->taskRepo->updateStatus($taskNo, 0, '进程丢失，重新排队');
                        $this->cleanupPidFile($taskNo);
                        error_log("[CollectWorker] reset orphaned task={$taskNo} to pending");
                    }
                }
                continue;
            }
            $this->runningTasks[$taskNo] = [
                'pid' => $pid,
                'started_at' => time(),
            ];
            error_log("[CollectWorker] recovered task={$taskNo} pid={$pid}");
        }
    }

    public function poll(): void
    {
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
            $imported = $this->questionRepo->importFromJsonl($jsonlFile, $taskNo);
            $esIndexed = 0;
            if ($imported > 0) {
                $questions = $this->questionRepo->findByTaskNo($taskNo);
                $esIndexed = $this->esRepo->bulkIndex($questions);
            }
            $esFailed = $imported - $esIndexed;
            $this->taskRepo->updateProgress($taskNo, $imported, $esIndexed, $esFailed);
            if ($esFailed > 0) {
                $this->taskRepo->updateStatus($taskNo, 4, "ES索引部分失败: {$esFailed}/{$imported}条未索引");
            } else {
                $this->taskRepo->updateStatus($taskNo, 2);
            }
            error_log("[CollectWorker] imported task={$taskNo} mongo={$imported} es_ok={$esIndexed} es_fail={$esFailed}");
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
        return isset($output[0]) && trim($output[0]) === '0';
    }

    protected function killProcess(int $pid): void
    {
        if ($pid > 0) {
            $output = [];
            @exec("kill {$pid} 2>/dev/null", $output);
        }
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
