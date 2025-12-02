<?php

namespace App\Repositories\Projects;

use App\Models\ActivityTypeModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Activity type repository.
 *
 * @agent-repository: Activity type
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ActivityTypeRepository
{
    protected ActivityTypeModel $activities;
    protected BaseConnection $db;

    public function __construct(?ActivityTypeModel $activities = null, ?BaseConnection $db = null)
    {
        $this->activities = $activities ?? new ActivityTypeModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->activities->insert($payload);
        $payload['id'] = (int) $this->activities->getInsertID();
        return $payload;
    }

    public function find(int $id): ?array
    {
        $row = $this->activities->find($id);
        return $row ?: null;
    }

    public function findByName(string $name): ?array
    {
        $row = $this->activities->where('activity_name', $name)->first();
        return $row ?: null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
