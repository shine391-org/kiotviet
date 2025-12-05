<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate pricing rules.
 *
 * @agent-validator: Pricing rule
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PricingRuleValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'condition_type' => 'required|in_list[amount,customer,project,product]',
            'customer_id' => 'permit_empty|integer|greater_than[0]',
            'project_id' => 'permit_empty|integer|greater_than[0]',
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'min_qty' => 'permit_empty|numeric',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'price' => 'permit_empty|numeric',
            'discount_percent' => 'permit_empty|numeric',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        $data['priority'] = $data['priority'] ?? 100;
        $data['min_qty'] = isset($data['min_qty']) ? (float) $data['min_qty'] : 0;
        $data['price'] = isset($data['price']) ? (float) $data['price'] : 0;
        $data['discount_percent'] = isset($data['discount_percent']) ? (float) $data['discount_percent'] : 0;
        $data['is_active'] = filter_var($data['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $data;
    }

    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'permit_empty|string|max_length[150]',
            'condition_type' => 'permit_empty|in_list[amount,customer,project,product]',
            'customer_id' => 'permit_empty|integer|greater_than[0]',
            'project_id' => 'permit_empty|integer|greater_than[0]',
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'min_qty' => 'permit_empty|numeric',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'price' => 'permit_empty|numeric',
            'discount_percent' => 'permit_empty|numeric',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
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
