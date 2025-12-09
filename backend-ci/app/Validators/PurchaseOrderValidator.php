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
        $partnerId = isset($input['partner_id']) ? (int) $input['partner_id'] : null;
        $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : null;
        $items = $input['items'] ?? [];
        
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items are required');
        }
        
        $normalizedItems = [];
        foreach ($items as $item) {
            $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0;
            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : $rate;
            $amount = isset($item['amount']) ? (float) $item['amount'] : round($qty * $unitPrice, 2);
            
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0');
            }
            $normalizedItems[] = [
                'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                'quantity' => $qty,
                'rate' => $rate,
                'unit_price' => $unitPrice,
                'total_price' => $amount,
                'amount' => $amount,
            ];
        }
        
        return [
            'branch_id' => $branchId,
            'partner_id' => $partnerId ?: $supplierId,
            'supplier_id' => $supplierId ?: $partnerId,
            'payment_method' => $input['payment_method'] ?? null,
            'status' => $input['status'] ?? 'draft',
            'order_date' => $input['order_date'] ?? date('Y-m-d H:i:s'),
            'notes' => $input['notes'] ?? null,
            'discount' => isset($input['discount']) ? (float) $input['discount'] : 0,
            'items' => $normalizedItems,
        ];
    }
}

