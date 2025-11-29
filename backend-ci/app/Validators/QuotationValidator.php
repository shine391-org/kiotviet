<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate quotations.
 *
 * @agent-validator: Quotation
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class QuotationValidator
{
    public function validate(array $input): array
    {
        $items = $input['items'] ?? [];
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $normalizedItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('product_id and quantity are required for items');
            }
            $normalizedItems[] = [
                'product_id' => $productId,
                'quantity' => $qty,
            ];
        }

        return [
            'opportunity_id' => isset($input['opportunity_id']) ? (int) $input['opportunity_id'] : null,
            'customer_id' => isset($input['customer_id']) ? (int) $input['customer_id'] : null,
            'lead_id' => isset($input['lead_id']) ? (int) $input['lead_id'] : null,
            'validity_date' => $input['validity_date'] ?? date('Y-m-d', strtotime('+7 days')),
            'status' => $input['status'] ?? 'draft',
            'items' => $normalizedItems,
        ];
    }
}
