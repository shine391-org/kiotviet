<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate payroll operations.
 *
 * @agent-validator: Payroll
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PayrollValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'period_start' => 'required|valid_date[Y-m-d]',
            'period_end' => 'required|valid_date[Y-m-d]',
            'employees' => 'required',
        ]);
        $employees = $this->normalizeEmployees($input['employees'] ?? []);
        if (empty($employees)) {
            throw new InvalidArgumentException('employees is required');
        }
        $data['employees'] = $employees;
        return $data;
    }

    private function normalizeEmployees($employees): array
    {
        if (! is_array($employees)) {
            return [];
        }
        $normalized = [];
        foreach ($employees as $emp) {
            $empId = (int) ($emp['employee_id'] ?? 0);
            if ($empId <= 0) {
                throw new InvalidArgumentException('employee_id is required');
            }
            $earnings = $this->normalizeComponents($emp['earnings'] ?? [], 'earning');
            $deductions = $this->normalizeComponents($emp['deductions'] ?? [], 'deduction');
            $normalized[] = [
                'employee_id' => $empId,
                'earnings' => $earnings,
                'deductions' => $deductions,
            ];
        }
        return $normalized;
    }

    private function normalizeComponents(array $components, string $type): array
    {
        $normalized = [];
        foreach ($components as $item) {
            $name = $item['component_name'] ?? null;
            $amount = isset($item['amount']) ? (float) $item['amount'] : 0.0;
            if (! $name || $amount < 0) {
                throw new InvalidArgumentException('component_name and amount are required');
            }
            $normalized[] = [
                'component_name' => $name,
                'component_type' => $type,
                'amount' => $amount,
            ];
        }
        return $normalized;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
