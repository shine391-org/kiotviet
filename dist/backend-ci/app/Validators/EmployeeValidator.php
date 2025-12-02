<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate employees.
 *
 * @agent-validator: Employee
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class EmployeeValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'full_name' => 'required|string|max_length[255]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'join_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        $data['status'] = $data['status'] ?? 'active';
        return $data;
    }

    public function validateUpdate(array $input): array
    {
        return $this->run($input, [
            'full_name' => 'permit_empty|string|max_length[255]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'join_date' => 'permit_empty|valid_date[Y-m-d]',
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
