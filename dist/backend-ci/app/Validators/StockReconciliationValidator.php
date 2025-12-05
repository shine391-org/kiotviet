<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate stock reconciliation payloads.
 *
 * @agent-validator: Stock reconciliation
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class StockReconciliationValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'branch_id' => 'required|integer|greater_than[0]',
            'notes' => 'permit_empty|string',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
            'items' => 'required',
        ]);
        $items = $this->normalizeItems($input['items'] ?? []);
        if (empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $data['items'] = $items;
        return $data;
    }

    public function validateApprove(array $input): array
    {
        return $this->run($input, [
            'approved_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
    }

    private function normalizeItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $counted = (float) ($item['counted_qty'] ?? 0);
            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required');
            }
            $normalized[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'batch_id' => isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                'counted_qty' => $counted,
                'unit_cost' => isset($item['unit_cost']) ? (float) $item['unit_cost'] : 0,
                'remarks' => $item['remarks'] ?? null,
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
