<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate pick list payloads.
 *
 * @agent-validator: Pick list
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PickListValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'stock_entry_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'source_warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        $items = $this->normalizeItems($input['items'] ?? []);
        if (empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $data['items'] = $items;
        return $data;
    }

    private function normalizeItems($items): array
    {
        if (! is_array($items)) {
            throw new InvalidArgumentException('items must be an array');
        }
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? ($item['quantity'] ?? 0));
            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('qty must be > 0');
            }
            $normalized[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'batch_id' => isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                'serial_number' => $item['serial_number'] ?? null,
                'source_warehouse_id' => isset($item['source_warehouse_id']) ? (int) $item['source_warehouse_id'] : null,
                'target_warehouse_id' => isset($item['target_warehouse_id']) ? (int) $item['target_warehouse_id'] : null,
            ];
        }
        return $normalized;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
