<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate customer/project price list mappings.
 *
 * @agent-validator: Customer price list
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CustomerPriceListValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'customer_id' => 'required|integer|greater_than[0]',
            'price_list_id' => 'required|integer|greater_than[0]',
            'valid_from' => 'permit_empty|valid_date[Y-m-d]',
            'valid_to' => 'permit_empty|valid_date[Y-m-d]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        $data['is_active'] = filter_var($data['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $this->assertDateOrder($data);
        return $data;
    }

    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'price_list_id' => 'permit_empty|integer|greater_than[0]',
            'valid_from' => 'permit_empty|valid_date[Y-m-d]',
            'valid_to' => 'permit_empty|valid_date[Y-m-d]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
        $this->assertDateOrder($data);
        return $data;
    }

    private function assertDateOrder(array $data): void
    {
        if (! empty($data['valid_from']) && ! empty($data['valid_to'])) {
            if (strtotime($data['valid_to']) < strtotime($data['valid_from'])) {
                throw new InvalidArgumentException('valid_to must be after valid_from');
            }
        }
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
