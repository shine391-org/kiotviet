<?php

namespace App\Repositories\Jobs;

use App\Models\JobModel;
use App\Models\JobLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Job queue repository.
 *
 * @agent-repository: Job queue
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class JobRepository
{
    protected JobModel $jobs;
    protected JobLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?JobModel $jobs = null, ?JobLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->jobs = $jobs ?? new JobModel();
        $this->logs = $logs ?? new JobLogModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function enqueue(array $data): array
    {
        $payload = $data + [
            'status' => 'queued',
            'attempts' => 0,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->jobs->insert($payload);
        $payload['id'] = (int) $this->jobs->getInsertID();
        return $payload;
    }

    public function claimNext(): ?array
    {
        $row = $this->db->table('job_queue')
            ->where('status', 'queued')
            ->where('next_run_at <=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();
        if (! $row) {
            return null;
        }
        $this->jobs->update((int) $row['id'], ['status' => 'running', 'updated_at' => $this->now()]);
        $row['status'] = 'running';
        return $row;
    }

    public function markSuccess(int $id): void
    {
        $this->jobs->update($id, ['status' => 'done', 'updated_at' => $this->now()]);
        $this->logs->insert([
            'job_id' => $id,
            'status' => 'done',
            'message' => 'Completed',
            'created_at' => $this->now(),
        ]);
    }

    public function markFailure(int $id, string $message, int $attempts, int $maxAttempts): void
    {
        $status = $attempts >= $maxAttempts ? 'failed' : 'queued';
        $nextRun = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $this->jobs->update($id, [
            'status' => $status,
            'attempts' => $attempts,
            'next_run_at' => $nextRun,
            'last_error' => $message,
            'updated_at' => $this->now(),
        ]);
        $this->logs->insert([
            'job_id' => $id,
            'status' => $status,
            'message' => $message,
            'created_at' => $this->now(),
        ]);
    }

    public function list(array $filters = []): array
    {
        $b = $this->jobs->builder();
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
