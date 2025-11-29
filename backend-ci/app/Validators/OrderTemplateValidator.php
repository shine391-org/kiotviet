<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate order templates.
 *
 * @agent-validator: Order templates
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class OrderTemplateValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate create payload. @agent-use: Create order template */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'frequency' => 'permit_empty|string|max_length[50]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'notes' => 'permit_empty|string',
        ]);
        $items = $input['items'] ?? null;
        $normalizedItems = $this->validateItems($items, true);
        $data['items'] = $normalizedItems;
        $data['is_active'] = array_key_exists('is_active', $data)
            ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            : true;
        return $data;
    }

    /** Validate update payload. */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'permit_empty|string|max_length[150]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'frequency' => 'permit_empty|string|max_length[50]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'notes' => 'permit_empty|string',
            'items' => 'permit_empty',
        ]);
        $items = array_key_exists('items', $input) ? $input['items'] : null;
        if ($items !== null) {
            $data['items'] = $this->validateItems($items, true);
        }
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        return $data;
    }

    /** Validate list filters. */
    public function validateFilters(array $input): array
    {
        $data = $this->run($input, [
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'search' => 'permit_empty|string|max_length[150]',
        ]);
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        return $data;
    }

    private function validateItems($items, bool $required): array
    {
        if (! is_array($items) || empty($items)) {
            if ($required) {
                throw new InvalidArgumentException('items is required');
            }
            return [];
        }
        $normalized = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid item at index ' . $idx);
            }
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required for item ' . $idx);
            }
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0 for item ' . $idx);
            }
            $normalized[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => $qty,
                'price' => isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : null,
                'notes' => isset($item['notes']) ? $this->stringOrNull($item['notes']) : null,
            ];
        }
        return $normalized;
    }

    private function stringOrNull($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
