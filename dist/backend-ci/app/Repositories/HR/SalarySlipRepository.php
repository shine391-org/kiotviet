<?php

namespace App\Repositories\HR;

use App\Models\SalarySlipModel;
use App\Models\SalaryComponentModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Salary slip repository.
 *
 * @agent-repository: Salary slip
 * @agent-pattern: Repository with components
 * @agent-reusable: MEDIUM
 */
class SalarySlipRepository
{
    protected SalarySlipModel $slips;
    protected SalaryComponentModel $components;
    protected BaseConnection $db;

    public function __construct(
        ?SalarySlipModel $slips = null,
        ?SalaryComponentModel $components = null,
        ?BaseConnection $db = null
    ) {
        $this->slips = $slips ?? new SalarySlipModel();
        $this->components = $components ?? new SalaryComponentModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $slip, array $components): array
    {
        $now = $this->now();
        $payload = $slip + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->slips->insert($payload);
        $slipId = (int) $this->slips->getInsertID();
        $rows = [];
        foreach ($components as $c) {
            $rows[] = $c + [
                'salary_slip_id' => $slipId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->components->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->find($slipId);
    }

    public function find(int $id): ?array
    {
        $row = $this->slips->find($id);
        if (! $row) {
            return null;
        }
        $components = $this->components->where('salary_slip_id', $id)->findAll();
        $row = $this->hydrateSlip($row);
        $row['components'] = array_map([$this, 'hydrateComponent'], $components);
        return $row;
    }

    public function list(array $filters = []): array
    {
        $b = $this->slips->builder();
        if (! empty($filters['payroll_entry_id'])) {
            $b->where('payroll_entry_id', $filters['payroll_entry_id']);
        }
        if (! empty($filters['employee_id'])) {
            $b->where('employee_id', $filters['employee_id']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->slips->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    private function hydrateSlip(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['payroll_entry_id'] = isset($row['payroll_entry_id']) ? (int) $row['payroll_entry_id'] : null;
        $row['employee_id'] = isset($row['employee_id']) ? (int) $row['employee_id'] : null;
        $row['total_earnings'] = isset($row['total_earnings']) ? (float) $row['total_earnings'] : 0.0;
        $row['total_deductions'] = isset($row['total_deductions']) ? (float) $row['total_deductions'] : 0.0;
        $row['net_pay'] = isset($row['net_pay']) ? (float) $row['net_pay'] : 0.0;
        return $row;
    }

    private function hydrateComponent(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['salary_slip_id'] = isset($row['salary_slip_id']) ? (int) $row['salary_slip_id'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
