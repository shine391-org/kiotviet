<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate reorder level inputs.
 *
 * @agent-validator: Reorder levels
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ReorderLevelValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /**
     * Validate reorder level creation.
     * @agent-use: Create reorder level
     * @agent-pattern: Strict numeric validation
     */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'min_level' => 'required|numeric|greater_than_equal_to[0]',
            'max_level' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'safety_stock' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        return $this->normalizeLevels($data, true);
    }

    /**
     * Validate reorder level update.
     * @agent-use: Update reorder level
     * @agent-pattern: Partial update validation
     */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'min_level' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'max_level' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'safety_stock' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }

        return $this->normalizeLevels($data, false);
    }

    /**
     * Validate filter parameters for listing.
     * @agent-use: Filter reorder levels
     * @agent-pattern: Lightweight filter validation
     */
    public function validateFilters(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        return $this->normalizeLevels($data, false);
    }

    private function normalizeLevels(array $data, bool $forCreate): array
    {
        if (array_key_exists('variant_id', $data)) {
            $data['variant_id'] = $data['variant_id'] === '' ? null : (int) $data['variant_id'];
        }

        foreach (['min_level', 'max_level', 'safety_stock'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = (float) $data[$field];
            }
        }

        if (! array_key_exists('safety_stock', $data) && $forCreate) {
            $data['safety_stock'] = 0.0;
        }
        if (! array_key_exists('max_level', $data) && $forCreate) {
            $data['max_level'] = 0.0;
        }
        if (! array_key_exists('is_active', $data) && $forCreate) {
            $data['is_active'] = true;
        } elseif (array_key_exists('is_active', $data)) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $min = $data['min_level'] ?? null;
        $max = $data['max_level'] ?? null;
        if ($max !== null && $max > 0 && $min !== null && $max < $min) {
            throw new InvalidArgumentException('max_level must be greater than or equal to min_level');
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
