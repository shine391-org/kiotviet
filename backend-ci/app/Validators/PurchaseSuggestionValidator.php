<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate purchase suggestion flows.
 *
 * @agent-validator: Purchase suggestions
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PurchaseSuggestionValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /**
     * Validate generation filters.
     * @agent-use: Generate purchase suggestions
     * @agent-pattern: Filter validation
     */
    public function validateGenerate(array $input): array
    {
        $data = $this->run($input, [
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'generated_for_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        return $this->normalizeFilters($data, true);
    }

    /**
     * Validate list filters.
     * @agent-use: List purchase suggestions
     * @agent-pattern: Filter validation
     */
    public function validateList(array $input): array
    {
        $data = $this->run($input, [
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|in_list[pending,acknowledged,converted,dismissed]',
            'generated_for_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        return $this->normalizeFilters($data, false);
    }

    /**
     * Validate acknowledge payload.
     * @agent-use: Acknowledge purchase suggestion
     * @agent-pattern: Simple status update
     */
    public function validateAcknowledge(array $input): array
    {
        return $this->run($input, [
            'acknowledged_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
    }

    /**
     * Validate conversion payload to purchase order.
     * @agent-use: Convert suggestion
     * @agent-pattern: Conversion validation
     */
    public function validateConvert(array $input): array
    {
        return $this->run($input, [
            'acknowledged_by' => 'permit_empty|integer|greater_than_equal_to[0]',
            'purchase_order_code' => 'permit_empty|string|max_length[50]',
        ]);
    }

    private function normalizeFilters(array $data, bool $defaultDate): array
    {
        if ($defaultDate && ! isset($data['generated_for_date'])) {
            $data['generated_for_date'] = date('Y-m-d');
        }
        foreach (['branch_id', 'product_id', 'variant_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $data[$key] === '' ? null : (int) $data[$key];
            }
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
