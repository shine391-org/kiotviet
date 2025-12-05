<?php

namespace App\Repositories\Projects;

use App\Models\TaskModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Task persistence.
 *
 * @agent-repository: Task
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class TaskRepository
{
    protected TaskModel $tasks;
    protected BaseConnection $db;

    public function __construct(?TaskModel $tasks = null, ?BaseConnection $db = null)
    {
        $this->tasks = $tasks ?? new TaskModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->tasks->insert($payload);
        $payload['id'] = (int) $this->tasks->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->tasks->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->tasks->find($id);
        return $row ?: null;
    }

    public function listByProject(?int $projectId = null): array
    {
        $b = $this->tasks->builder();
        if ($projectId !== null) {
            $b->where('project_id', $projectId);
        }
        return $b->orderBy('id', 'ASC')->limit(500)->get()->getResultArray();
    }

    public function updateStatus(int $id, string $status, ?float $progress = null): void
    {
        $payload = ['status' => $status, 'updated_at' => $this->now()];
        if ($progress !== null) {
            $payload['progress'] = $progress;
        }
        $this->tasks->update($id, $payload);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
