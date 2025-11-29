<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate goods receipts.
 *
 * @agent-validator: GoodsReceipt
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class GoodsReceiptValidator
{
    public function validateCreate(array $input): array
    {
        $poId = isset($input['purchase_order_id']) ? (int) $input['purchase_order_id'] : null;
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
            'purchase_order_id' => $poId,
            'branch_id' => isset($input['branch_id']) ? (int) $input['branch_id'] : null,
            'items' => $normalizedItems,
        ];
    }
}
