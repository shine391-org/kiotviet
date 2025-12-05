<?php

namespace App\Repositories\HR;

use App\Models\LeaveApplicationModel;
use App\Models\LeaveTypeModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Leave persistence.
 *
 * @agent-repository: Leave
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class LeaveRepository
{
    protected LeaveApplicationModel $leaves;
    protected LeaveTypeModel $types;
    protected BaseConnection $db;

    public function __construct(
        ?LeaveApplicationModel $leaves = null,
        ?LeaveTypeModel $types = null,
        ?BaseConnection $db = null
    ) {
        $this->leaves = $leaves ?? new LeaveApplicationModel();
        $this->types = $types ?? new LeaveTypeModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function ensureType(string $name, float $allocation = 12): array
    {
        $row = $this->types->where('leave_name', $name)->first();
        if ($row) {
            return $row;
        }
        $payload = [
            'leave_name' => $name,
            'default_allocation' => $allocation,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->types->insert($payload);
        $payload['id'] = (int) $this->types->getInsertID();
        return $payload;
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->leaves->insert($payload);
        $payload['id'] = (int) $this->leaves->getInsertID();
        return $payload;
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $this->leaves->update($id, $extra + ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->leaves->find($id);
        return $row ?: null;
    }

    public function list(array $filters = []): array
    {
        $b = $this->leaves->builder();
        if (! empty($filters['employee_id'])) {
            $b->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function approvedDays(int $employeeId, int $leaveTypeId): float
    {
        $row = $this->leaves->selectSum('total_days')
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->get()->getRowArray();
        return (float) ($row['total_days'] ?? 0);
    }

    public function typeAllocation(int $leaveTypeId): float
    {
        $row = $this->types->find($leaveTypeId);
        return (float) ($row['default_allocation'] ?? 0);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
