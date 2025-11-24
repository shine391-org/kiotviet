<?php

namespace App\Repositories\Branches;

use App\Models\BranchModel;
use CodeIgniter\Database\BaseConnection;

/** Branch persistence. @agent-repository: Branches @agent-pattern: Repository pattern @agent-reusable: MEDIUM */
class BranchRepository
{
    protected BranchModel $branches;
    protected BaseConnection $db;

    public function __construct(?BranchModel $branches = null, ?BaseConnection $db = null)
    {
        $this->branches = $branches ?? new BranchModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function findAll(array $filters = []): array
    {
        $b = $this->branches->builder()->where('deleted_at', null);
        if (! empty($filters['is_active'])) {
            $b->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        return $b->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->branches->insert($payload);
        $payload['id'] = $this->branches->getInsertID();
        return $payload;
    }
}
