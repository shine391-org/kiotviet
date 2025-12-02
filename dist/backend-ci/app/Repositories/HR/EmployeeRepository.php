<?php

namespace App\Repositories\HR;

use App\Models\EmployeeModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Employee persistence.
 *
 * @agent-repository: Employee
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class EmployeeRepository
{
    protected EmployeeModel $employees;
    protected BaseConnection $db;

    public function __construct(?EmployeeModel $employees = null, ?BaseConnection $db = null)
    {
        $this->employees = $employees ?? new EmployeeModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function nextCode(): string
    {
        $prefix = 'EMP-' . date('Ymd');
        $count = $this->employees->where('employee_code LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->employees->insert($payload);
        $payload['id'] = (int) $this->employees->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->employees->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->employees->find($id);
        return $row ?: null;
    }

    public function list(array $filters = []): array
    {
        $b = $this->employees->builder();
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $b->like('full_name', $filters['search'])->orLike('employee_code', $filters['search']);
        }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
