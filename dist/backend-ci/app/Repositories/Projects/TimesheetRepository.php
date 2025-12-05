<?php

namespace App\Repositories\Projects;

use App\Models\TimesheetModel;
use App\Models\TimesheetDetailModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Timesheet persistence.
 *
 * @agent-repository: Timesheet
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class TimesheetRepository
{
    protected TimesheetModel $timesheets;
    protected TimesheetDetailModel $details;
    protected BaseConnection $db;

    public function __construct(?TimesheetModel $timesheets = null, ?TimesheetDetailModel $details = null, ?BaseConnection $db = null)
    {
        $this->timesheets = $timesheets ?? new TimesheetModel();
        $this->details = $details ?? new TimesheetDetailModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $header, array $items): array
    {
        $now = $this->now();
        $payload = $header + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->timesheets->insert($payload);
        $id = (int) $this->timesheets->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'timesheet_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->details->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id);
    }

    public function updateTotals(int $id, float $hours, float $billable, float $cost): void
    {
        $this->timesheets->update($id, [
            'total_hours' => $hours,
            'total_billable' => $billable,
            'total_cost' => $cost,
            'updated_at' => $this->now(),
        ]);
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $payload = $extra + ['status' => $status, 'updated_at' => $this->now()];
        $this->timesheets->update($id, $payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->timesheets->find($id);
        if (! $row) {
            return null;
        }
        $items = $this->details->where('timesheet_id', $id)->findAll();
        $row = $this->hydrateTimesheet($row);
        $row['items'] = array_map([$this, 'hydrateDetail'], $items);
        return $row;
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('timesheets');
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['project_id'])) {
            $b->where('project_id', $filters['project_id']);
        }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function nextNumber(): string
    {
        $prefix = 'TS-' . date('Ymd');
        $count = $this->timesheets->where('timesheet_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrateTimesheet(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['project_id'] = isset($row['project_id']) ? (int) $row['project_id'] : null;
        $row['employee_id'] = isset($row['employee_id']) ? (int) $row['employee_id'] : null;
        $row['total_hours'] = isset($row['total_hours']) ? (float) $row['total_hours'] : 0.0;
        $row['total_billable'] = isset($row['total_billable']) ? (float) $row['total_billable'] : 0.0;
        $row['total_cost'] = isset($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
        return $row;
    }

    private function hydrateDetail(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['timesheet_id'] = isset($row['timesheet_id']) ? (int) $row['timesheet_id'] : null;
        $row['project_id'] = isset($row['project_id']) ? (int) $row['project_id'] : null;
        $row['task_id'] = isset($row['task_id']) ? (int) $row['task_id'] : null;
        $row['activity_type_id'] = isset($row['activity_type_id']) ? (int) $row['activity_type_id'] : null;
        $row['hours'] = isset($row['hours']) ? (float) $row['hours'] : 0.0;
        $row['billing_rate'] = isset($row['billing_rate']) ? (float) $row['billing_rate'] : 0.0;
        $row['cost_rate'] = isset($row['cost_rate']) ? (float) $row['cost_rate'] : 0.0;
        $row['billable_amount'] = isset($row['billable_amount']) ? (float) $row['billable_amount'] : 0.0;
        $row['cost_amount'] = isset($row['cost_amount']) ? (float) $row['cost_amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
