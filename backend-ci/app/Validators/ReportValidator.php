<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate report filters.
 *
 * @agent-validator: Report
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ReportValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateDateRange(array $input): array
    {
        $data = $this->run($input, [
            'from_date' => 'permit_empty|valid_date[Y-m-d]',
            'to_date' => 'permit_empty|valid_date[Y-m-d]',
            'account_id' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        return $data;
    }

    public function validateAging(array $input): array
    {
        $data = $this->run($input, [
            'party_type' => 'required|string|max_length[60]',
            'as_of_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        $data['as_of_date'] = $data['as_of_date'] ?? date('Y-m-d');
        return $data;
    }

    public function validateStock(array $input): array
    {
        return $this->run($input, [
            'warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'product_id' => 'permit_empty|integer|greater_than_equal_to[0]',
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
