<?php

namespace App\Repositories\HR;

use App\Models\AttendanceModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Attendance persistence.
 *
 * @agent-repository: Attendance
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class AttendanceRepository
{
    protected AttendanceModel $attendances;
    protected BaseConnection $db;

    public function __construct(?AttendanceModel $attendances = null, ?BaseConnection $db = null)
    {
        $this->attendances = $attendances ?? new AttendanceModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function log(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->attendances->insert($payload);
        $payload['id'] = (int) $this->attendances->getInsertID();
        return $payload;
    }

    public function list(array $filters = []): array
    {
        $b = $this->attendances->builder();
        if (! empty($filters['employee_id'])) {
            $b->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['from_date'])) {
            $b->where('attendance_date >=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $b->where('attendance_date <=', $filters['to_date']);
        }
        return $b->orderBy('attendance_date', 'DESC')->limit(200)->get()->getResultArray();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
