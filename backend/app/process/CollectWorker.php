<?php

namespace app\process;

use app\repository\es\QuestionIndexRepository;
use app\repository\mongo\QuestionRepository;
use app\repository\mysql\CollectTaskRepository;
use Workerman\Timer;

class CollectWorker
{
    protected CollectTaskRepository $taskRepo;
    protected QuestionRepository $questionRepo;
    protected QuestionIndexRepository $esRepo;
    protected array $runningTasks = [];

    public function onWorkerStart(): void
    {
        $this->taskRepo = new CollectTaskRepository();
        $this->questionRepo = new QuestionRepository();
        $this->esRepo = new QuestionIndexRepository();

        Timer::add(5, [$this, 'poll']);
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

        $projectRoot = dirname(__DIR__, 3);
        $pythonDir = $projectRoot . '/xxt';
        $resultsDir = $pythonDir . '/results';
        if (!is_dir($resultsDir)) {
            @mkdir($resultsDir, 0755, true);
        }

        $cmd = sprintf(
            'cd %s && python3 run.py --account %s --mode %s --output json --task-no %s --concurrency 1',
            escapeshellarg($pythonDir),
            escapeshellarg($phone . '----' . $password),
            escapeshellarg($mode),
            escapeshellarg($taskNo)
        );

        if ($courseIds !== '') {
            $cmd .= ' --course-ids ' . escapeshellarg($courseIds);
        }

        $cmd .= ' > /dev/null 2>&1 & echo $!';

        $output = [];
        exec($cmd, $output);
        $pid = (int) ($output[0] ?? 0);
        return $pid;
    }

    protected function checkRunningTasks(): void
    {
        foreach ($this->runningTasks as $taskNo => $info) {
            $pid = $info['pid'];
            $isRunning = $this->isProcessRunning($pid);

            if ($isRunning) {
                if (time() - $info['started_at'] > 7200) {
                    $this->killProcess($pid);
                    $this->taskRepo->updateStatus($taskNo, 3, '采集超时(2h)');
                    unset($this->runningTasks[$taskNo]);
                    error_log("[CollectWorker] timeout task={$taskNo} pid={$pid}");
                }
                continue;
            }

            unset($this->runningTasks[$taskNo]);
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
            if ($imported > 0) {
                $questions = $this->questionRepo->findByTaskNo($taskNo);
                $this->esRepo->bulkIndex($questions);
            }
            $this->taskRepo->updateProgress($taskNo, $imported, $imported, 0);
            $this->taskRepo->updateStatus($taskNo, 2);
            error_log("[CollectWorker] imported task={$taskNo} questions={$imported}");
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
        $result = shell_exec("kill -0 {$pid} 2>/dev/null; echo \$?");
        return trim($result) === '0';
    }

    protected function killProcess(int $pid): void
    {
        if ($pid > 0) {
            @exec("kill {$pid} 2>/dev/null");
        }
    }
}
