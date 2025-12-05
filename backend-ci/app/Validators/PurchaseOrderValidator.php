<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate purchase orders.
 *
 * @agent-validator: PurchaseOrder
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PurchaseOrderValidator
{
    public function validateCreate(array $input): array
    {
        $branchId = isset($input['branch_id']) ? (int) $input['branch_id'] : null;
        $items = $input['items'] ?? [];
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items are required');
        }
        $normalizedItems = [];
        foreach ($items as $item) {
            $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0;
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0');
            }
            $normalizedItems[] = [
                'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                'quantity' => $qty,
                'rate' => $rate,
                'amount' => round($qty * $rate, 2),
            ];
        }
        return [
            'branch_id' => $branchId,
            'payment_method' => $input['payment_method'] ?? null,
            'status' => $input['status'] ?? 'draft',
            'items' => $normalizedItems,
        ];
    }
}
