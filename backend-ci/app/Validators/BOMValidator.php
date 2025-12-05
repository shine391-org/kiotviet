<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate bill of materials input.
 *
 * @agent-validator: BOM
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class BOMValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate BOM creation. */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'version' => 'permit_empty|string|max_length[50]',
            'quantity' => 'permit_empty|numeric|greater_than[0]',
            'uom' => 'permit_empty|string|max_length[50]',
            'cost' => 'permit_empty|numeric',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        $items = $this->validateItems($input['items'] ?? null);
        $data['items'] = $items;
        $data['quantity'] = isset($data['quantity']) ? (float) $data['quantity'] : 1.0;
        $data['cost'] = isset($data['cost']) ? (float) $data['cost'] : 0.0;
        $data['is_active'] = array_key_exists('is_active', $data)
            ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            : true;
        return $data;
    }

    /** Validate BOM update (partial). */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'version' => 'permit_empty|string|max_length[50]',
            'quantity' => 'permit_empty|numeric|greater_than[0]',
            'uom' => 'permit_empty|string|max_length[50]',
            'cost' => 'permit_empty|numeric',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'items' => 'permit_empty',
        ]);
        if (array_key_exists('items', $input)) {
            $data['items'] = $this->validateItems($input['items']);
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
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'version' => 'permit_empty|string|max_length[50]',
        ]);
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        return $data;
    }

    private function validateItems($items): array
    {
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $normalized = [];
        $seen = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid item at index ' . $idx);
            }
            $component = (int) ($item['component_product_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($component <= 0) {
                throw new InvalidArgumentException('component_product_id is required for item ' . $idx);
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0 for item ' . $idx);
            }
            if (isset($seen[$component])) {
                throw new InvalidArgumentException('Duplicate component ' . $component);
            }
            $seen[$component] = true;
            $normalized[] = [
                'component_product_id' => $component,
                'quantity' => $qty,
                'uom' => isset($item['uom']) ? $this->stringOrNull($item['uom']) : null,
                'scrap_percent' => isset($item['scrap_percent']) && $item['scrap_percent'] !== '' ? (float) $item['scrap_percent'] : 0.0,
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
