<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate landed cost vouchers.
 *
 * @agent-validator: LandedCost
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class LandedCostValidator
{
    public function validateCreate(array $input): array
    {
        $grnId = isset($input['goods_receipt_id']) ? (int) $input['goods_receipt_id'] : 0;
        if ($grnId <= 0) {
            throw new InvalidArgumentException('goods_receipt_id is required');
        }
        $items = $input['items'] ?? [];
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items are required');
        }
        $normalized = [];
        foreach ($items as $item) {
            $amount = isset($item['amount']) ? (float) $item['amount'] : 0;
            if ($amount <= 0) {
                throw new InvalidArgumentException('amount must be > 0');
            }
            $normalized[] = [
                'goods_receipt_item_id' => isset($item['goods_receipt_item_id']) ? (int) $item['goods_receipt_item_id'] : null,
                'cost_component' => $item['cost_component'] ?? 'Other',
                'amount' => $amount,
            ];
        }
        return [
            'goods_receipt_id' => $grnId,
            'items' => $normalized,
        ];
    }
}
