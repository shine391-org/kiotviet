<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate subcontracting orders.
 *
 * @agent-validator: Subcontracting
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class SubcontractingValidator
{
    public function validateCreate(array $input): array
    {
        $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : 0;
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('supplier_id is required');
        }
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : 0;
        if ($productId <= 0) {
            throw new InvalidArgumentException('product_id is required');
        }
        $qty = isset($input['quantity']) ? (float) $input['quantity'] : 0;
        if ($qty <= 0) {
            throw new InvalidArgumentException('quantity must be > 0');
        }
        $materials = $input['materials'] ?? [];
        if (! is_array($materials) || empty($materials)) {
            throw new InvalidArgumentException('materials are required');
        }
        $normalized = [];
        foreach ($materials as $m) {
            $mQty = isset($m['quantity']) ? (float) $m['quantity'] : 0;
            if ($mQty <= 0) {
                throw new InvalidArgumentException('material quantity must be > 0');
            }
            $normalized[] = [
                'material_product_id' => isset($m['material_product_id']) ? (int) $m['material_product_id'] : null,
                'quantity' => $mQty,
            ];
        }
        return [
            'supplier_id' => $supplierId,
            'product_id' => $productId,
            'quantity' => $qty,
            'materials' => $normalized,
        ];
    }
}
