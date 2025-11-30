<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate stock entry payloads.
 *
 * @agent-validator: Stock entry
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class StockEntryValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'type' => 'required|string|max_length[30]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'source_warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'target_warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'reference_type' => 'permit_empty|string|max_length[80]',
            'reference_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'return_reason' => 'permit_empty|string|max_length[255]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);

        $type = strtolower($data['type']);
        if (! in_array($type, ['issue', 'receipt', 'transfer', 'return'], true)) {
            throw new InvalidArgumentException('type must be issue, receipt, transfer, or return');
        }
        if ($type === 'return' && empty($data['return_reason'])) {
            throw new InvalidArgumentException('return_reason is required for return');
        }

        $items = $this->normalizeItems($input['items'] ?? [], $data, $type);
        if (empty($items)) {
            throw new InvalidArgumentException('items is required');
        }

        return [
            'type' => $type,
            'branch_id' => $data['branch_id'] ?? null,
            'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
            'target_warehouse_id' => $data['target_warehouse_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'return_reason' => $data['return_reason'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'items' => $items,
        ];
    }

    /**
     * @param array $items
     * @param array $parent
     * @param string $type
     */
    private function normalizeItems($items, array $parent, string $type): array
    {
        if (! is_array($items)) {
            throw new InvalidArgumentException('items must be an array');
        }
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? ($item['quantity'] ?? 0));
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $batchId = isset($item['batch_id']) ? (int) $item['batch_id'] : null;
            $sourceWarehouse = isset($item['source_warehouse_id']) ? (int) $item['source_warehouse_id'] : ($parent['source_warehouse_id'] ?? null);
            $targetWarehouse = isset($item['target_warehouse_id']) ? (int) $item['target_warehouse_id'] : ($parent['target_warehouse_id'] ?? null);
            $sourceBranch = isset($item['source_branch_id']) ? (int) $item['source_branch_id'] : null;
            $targetBranch = isset($item['target_branch_id']) ? (int) $item['target_branch_id'] : null;

            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('qty must be > 0');
            }
            if ($type === 'issue' && $sourceWarehouse === null && $sourceBranch === null) {
                throw new InvalidArgumentException('source_warehouse_id is required for issue');
            }
            if ($type === 'receipt' && $targetWarehouse === null && $targetBranch === null) {
                throw new InvalidArgumentException('target_warehouse_id is required for receipt');
            }
            if ($type === 'transfer' && ($sourceWarehouse === null || $targetWarehouse === null)) {
                throw new InvalidArgumentException('source_warehouse_id and target_warehouse_id are required for transfer');
            }
            if ($type === 'return' && $targetWarehouse === null && $targetBranch === null) {
                throw new InvalidArgumentException('target_warehouse_id is required for return');
            }

            $normalized[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => $qty,
                'uom' => $item['uom'] ?? null,
                'batch_id' => $batchId,
                'serial_number' => $item['serial_number'] ?? null,
                'source_warehouse_id' => $sourceWarehouse,
                'target_warehouse_id' => $targetWarehouse,
                'source_branch_id' => $sourceBranch,
                'target_branch_id' => $targetBranch,
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
