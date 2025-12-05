<?php

namespace App\Repositories\Projects;

use App\Models\ProjectModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Project persistence.
 *
 * @agent-repository: Project
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ProjectRepository
{
    protected ProjectModel $projects;
    protected BaseConnection $db;

    public function __construct(?ProjectModel $projects = null, ?BaseConnection $db = null)
    {
        $this->projects = $projects ?? new ProjectModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->projects->insert($payload);
        $payload['id'] = (int) $this->projects->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->projects->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->projects->find($id);
        return $row ?: null;
    }

    public function list(array $filters = []): array
    {
        $b = $this->projects->builder()->where('1=1');
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $b->like('project_name', $filters['search'])->orLike('project_code', $filters['search']);
        }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function updateProgress(int $id, float $progress): void
    {
        $this->projects->update($id, ['progress' => $progress, 'updated_at' => $this->now()]);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
