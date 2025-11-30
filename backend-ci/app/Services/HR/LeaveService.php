<?php

namespace App\Services\HR;

use App\Repositories\HR\LeaveRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Validators\LeaveValidator;
use RuntimeException;

/**
 * Leave service.
 *
 * @agent-service: Leave
 * @agent-pattern: Apply + approve with balance check
 * @agent-reusable: MEDIUM
 */
class LeaveService
{
    protected LeaveRepository $repo;
    protected EmployeeRepository $employees;
    protected LeaveValidator $validator;

    public function __construct(
        ?LeaveRepository $repo = null,
        ?EmployeeRepository $employees = null,
        ?LeaveValidator $validator = null
    ) {
        $this->repo = $repo ?? new LeaveRepository();
        $this->employees = $employees ?? new EmployeeRepository();
        $this->validator = $validator ?? new LeaveValidator();
    }

    /** @agent-use: GET /api/leaves */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    /** @agent-use: POST /api/leaves/apply */
    public function apply(array $input): array
    {
        $data = $this->validator->validateApply($input);
        $this->requireEmployee((int) $data['employee_id']);
        $this->repo->ensureType('Annual', 12.0);
        $balance = $this->balance((int) $data['employee_id'], (int) $data['leave_type_id']);
        if ($balance < $data['total_days']) {
            throw new RuntimeException('Insufficient leave balance');
        }
        $created = $this->repo->create($data);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: POST /api/leaves/{id}/approve */
    public function approve(int $id, array $input): array
    {
        $leave = $this->requireLeave($id);
        if ($leave['status'] !== 'pending') {
            throw new RuntimeException('Only pending leave can be approved');
        }
        $data = $this->validator->validateApprove($input);
        $this->repo->updateStatus($id, 'approved', [
            'approved_by' => $data['approved_by'] ?? null,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'data' => $this->requireLeave($id)];
    }

    /** @agent-use: GET /api/leaves/balance?employee_id=&leave_type_id= */
    public function balance(int $employeeId, int $leaveTypeId): float
    {
        $allocation = $this->repo->typeAllocation($leaveTypeId);
        $used = $this->repo->approvedDays($employeeId, $leaveTypeId);
        return max(0, $allocation - $used);
    }

    private function requireEmployee(int $id): array
    {
        $row = $this->employees->find($id);
        if (! $row) {
            throw new RuntimeException('Employee not found');
        }
        return $row;
    }

    private function requireLeave(int $id): array
    {
        $row = $this->repo->find($id);
        if (! $row) {
            throw new RuntimeException('Leave not found');
        }
        return $row;
    }
}
