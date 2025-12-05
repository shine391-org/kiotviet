<?php

namespace App\Services\HR;

use App\Repositories\HR\AttendanceRepository;
use App\Repositories\HR\EmployeeRepository;
use RuntimeException;

/**
 * Attendance service.
 *
 * @agent-service: Attendance
 * @agent-pattern: Log attendance
 * @agent-reusable: MEDIUM
 */
class AttendanceService
{
    protected AttendanceRepository $repo;
    protected EmployeeRepository $employees;

    public function __construct(?AttendanceRepository $repo = null, ?EmployeeRepository $employees = null)
    {
        $this->repo = $repo ?? new AttendanceRepository();
        $this->employees = $employees ?? new EmployeeRepository();
    }

    /** @agent-use: POST /api/attendances */
    public function log(array $input): array
    {
        $employeeId = (int) ($input['employee_id'] ?? 0);
        $date = $input['attendance_date'] ?? null;
        $status = $input['status'] ?? 'present';
        if ($employeeId <= 0 || ! $date) {
            throw new \InvalidArgumentException('employee_id and attendance_date required');
        }
        $this->requireEmployee($employeeId);
        $att = $this->repo->log([
            'employee_id' => $employeeId,
            'attendance_date' => $date,
            'status' => $status,
        ]);
        return ['success' => true, 'data' => $att];
    }

    /** @agent-use: GET /api/attendances */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    private function requireEmployee(int $id): void
    {
        if (! $this->employees->find($id)) {
            throw new RuntimeException('Employee not found');
        }
    }
}
