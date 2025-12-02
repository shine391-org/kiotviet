<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate work order inputs.
 *
 * @agent-validator: Work orders
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class WorkOrderValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate work order creation. */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'bom_id' => 'required|integer|greater_than[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'quantity' => 'required|numeric|greater_than[0]',
            'planned_start' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'planned_end' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ]);
        $data['quantity'] = (float) $data['quantity'];
        return $data;
    }

    /** Validate status update payload. */
    public function validateStatus(array $input): array
    {
        return $this->run($input, [
            'status' => 'required|string|max_length[30]',
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
