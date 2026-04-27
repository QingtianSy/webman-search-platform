<?php

namespace app\repository\mysql;

use PDO;
use support\adapter\MySqlClient;

class CollectTaskRepository
{
    public function all(): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC LIMIT 10000');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] all failed: " . $e->getMessage());
            return [];
        }
    }

    public function countAll(?int $userId = null): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM collect_tasks WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $userId]);
            } else {
                $stmt = $pdo->query('SELECT COUNT(*) FROM collect_tasks');
            }
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] countAll failed: " . $e->getMessage());
            return 0;
        }
    }

    public function findPage(int $page, int $pageSize, ?int $userId = null): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $offset = ($page - 1) * $pageSize;
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
                $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC LIMIT :limit OFFSET :offset');
            }
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] findPage failed: " . $e->getMessage());
            return [];
        }
    }

    // 严格版本：DB 故障抛 RuntimeException，避免"任务列表因 DB 挂了返空 → 前端以为无任务"。
    public function countAllStrict(?int $userId = null): int
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM collect_tasks WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $userId]);
            } else {
                $stmt = $pdo->query('SELECT COUNT(*) FROM collect_tasks');
            }
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new \RuntimeException('collect task count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findPageStrict(int $page, int $pageSize, ?int $userId = null): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $offset = ($page - 1) * $pageSize;
            if ($userId !== null && $userId > 0) {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset');
                $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks ORDER BY id DESC LIMIT :limit OFFSET :offset');
            }
            $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('collect task page failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listByUserId(int $userId): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, task_no, user_id, account_id, account_phone, collect_type, course_count, question_count, success_count, fail_count, status, error_message, runner_script, next_script, created_at FROM collect_tasks WHERE user_id = :user_id ORDER BY id DESC LIMIT 10000');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] listByUserId failed: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus(string $taskNo, int $status, string $errorMessage = ''): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return false;
        }
        try {
            $stmt = $pdo->prepare('UPDATE collect_tasks SET status = :status, error_message = :error_message, updated_at = NOW() WHERE task_no = :task_no');
            $stmt->execute([
                'task_no' => $taskNo,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] updateStatus failed: " . $e->getMessage());
            return false;
        }
    }

    // Strict：PDO 不可用或异常直接抛 RuntimeException。给 CollectWorker 启动恢复用：
    // orphan 分支把 status=1 任务回退成 pending 时，如果 DB 抖动 update 失败而 worker 又把 recoveryDone 置 true，
    // 这批任务永远不会再被重新认领。strict 变体让调用方可以感知失败并把 recoveryDone 保持为 false。
    public function updateStatusStrict(string $taskNo, int $status, string $errorMessage = ''): bool
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('UPDATE collect_tasks SET status = :status, error_message = :error_message, updated_at = NOW() WHERE task_no = :task_no');
            $stmt->execute([
                'task_no' => $taskNo,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new \RuntimeException('updateStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function clearRunnerScript(string $taskNo): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE collect_tasks SET runner_script = NULL, next_script = NULL WHERE task_no = :task_no');
            $stmt->execute(['task_no' => $taskNo]);
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] clearRunnerScript failed: " . $e->getMessage());
        }
    }

    public function create(array $data): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO collect_tasks (task_no, user_id, account_id, account_phone, account_password, school_name, province, city, proxy_url, collect_type, course_ids, course_count, courses_snapshot, status, created_at, updated_at) '
                . 'VALUES (:task_no, :user_id, :account_id, :account_phone, :account_password, :school_name, :province, :city, :proxy_url, :collect_type, :course_ids, :course_count, :courses_snapshot, 0, NOW(), NOW())'
            );
            $stmt->execute([
                'task_no' => $data['task_no'],
                'user_id' => $data['user_id'],
                'account_id' => $data['account_id'] ?? 0,
                'account_phone' => $data['account_phone'] ?? '',
                'account_password' => $data['account_password'] ?? '',
                'school_name' => $data['school_name'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'proxy_url' => $data['proxy_url'] ?? null,
                'collect_type' => $data['collect_type'] ?? 'courses',
                'course_ids' => $data['course_ids'] ?? '',
                'course_count' => $data['course_count'] ?? 0,
                'courses_snapshot' => $data['courses_snapshot'] ?? '',
            ]);
            return $data;
        } catch (\PDOException $e) {
            error_log("[CollectTaskRepository] create failed: " . $e->getMessage());
            return [];
        }
    }

    public function claimPending(): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE status = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE');
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) {
                $pdo->commit();
                return null;
            }
            $update = $pdo->prepare('UPDATE collect_tasks SET status = 1, updated_at = NOW() WHERE id = :id');
            $update->execute(['id' => $task['id']]);
            $pdo->commit();
            $task['status'] = 1;
            return $task;
        } catch (\Throwable $e) {
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (\Throwable) {
            }
            error_log("[CollectTaskRepository] claimPending failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateRunnerPid(string $taskNo, int $pid): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare('UPDATE collect_tasks SET runner_script = :pid, updated_at = NOW() WHERE task_no = :task_no');
        $stmt->execute(['pid' => (string) $pid, 'task_no' => $taskNo]);
    }

    public function updateProgress(string $taskNo, int $questionCount, int $successCount, int $failCount): void
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare(
            'UPDATE collect_tasks SET question_count = :qc, success_count = :sc, fail_count = :fc, updated_at = NOW() WHERE task_no = :task_no'
        );
        $stmt->execute([
            'qc' => $questionCount,
            'sc' => $successCount,
            'fc' => $failCount,
            'task_no' => $taskNo,
        ]);
    }

    public function findByTaskNo(string $taskNo): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE task_no = :task_no LIMIT 1');
        $stmt->execute(['task_no' => $taskNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // 严格版本：DB 故障抛 RuntimeException；null = 任务真的不存在。
    // 原 findByTaskNo 在 PDO 不可用时返 null，被 stop/retry/worker importResults 误解读为"任务不存在"，
    // 后台 stop/retry 会回 40001"任务不存在"，worker 则可能继续把已手停的任务状态覆盖回成功。
    public function findByTaskNoStrict(string $taskNo): ?array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE task_no = :task_no LIMIT 1');
            $stmt->execute(['task_no' => $taskNo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException('collect task findByTaskNo failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByStatus(int $status): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE status = :status ORDER BY id ASC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // 严格版本：DB 故障抛 RuntimeException；空数组 = 真的没有匹配行。
    // CollectWorker::recoverRunningTasks 依赖此列表来认领 status=1 的旧任务；
    // worker 启动时 MySQL 抖一下，非严格版本会吞掉错误返 []，runningTasks 永不建立，
    // 旧任务就永远卡在 status=1 直到管理员手动 stop/retry。
    public function findByStatusStrict(int $status): array
    {
        $pdo = MySqlClient::pdo();
        if (!$pdo) {
            throw new \RuntimeException('MySQL connection unavailable');
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM collect_tasks WHERE status = :status ORDER BY id ASC');
            $stmt->execute(['status' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            throw new \RuntimeException('collect task findByStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
