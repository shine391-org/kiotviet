<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate depreciation data.
 *
 * @agent-validator: Depreciation
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class DepreciationValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateSchedule(array $input): array
    {
        $data = $this->run($input, [
            'asset_id' => 'required|integer|greater_than[0]',
            'method' => 'permit_empty|string|max_length[50]',
            'rate' => 'required|decimal',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'total_periods' => 'permit_empty|integer|greater_than[0]',
        ]);
        $data['method'] = $data['method'] ?? 'straight_line';
        $data['rate'] = (float) $data['rate'];
        $data['total_periods'] = $data['total_periods'] ?? 12;
        return $data;
    }

    public function validatePost(array $input): array
    {
        return $this->run($input, [
            'schedule_id' => 'required|integer|greater_than[0]',
            'period_no' => 'required|integer|greater_than[0]',
            'account_id' => 'permit_empty|integer|greater_than[0]',
            'expense_account_id' => 'permit_empty|integer|greater_than[0]',
        ]);
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
