<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate leave operations.
 *
 * @agent-validator: Leave
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class LeaveValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateApply(array $input): array
    {
        $data = $this->run($input, [
            'employee_id' => 'required|integer|greater_than[0]',
            'leave_type_id' => 'required|integer|greater_than[0]',
            'from_date' => 'required|valid_date[Y-m-d]',
            'to_date' => 'required|valid_date[Y-m-d]',
            'reason' => 'permit_empty|string',
        ]);
        $from = strtotime($data['from_date']);
        $to = strtotime($data['to_date']);
        if ($from > $to) {
            throw new InvalidArgumentException('from_date must be before to_date');
        }
        $days = round(($to - $from) / 86400, 2) + 1;
        $data['total_days'] = $days;
        $data['status'] = 'pending';
        return $data;
    }

    public function validateApprove(array $input): array
    {
        $data = $this->run($input, [
            'approved_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        return $data;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
