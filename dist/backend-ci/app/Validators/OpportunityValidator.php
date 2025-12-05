<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate opportunities.
 *
 * @agent-validator: Opportunity
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class OpportunityValidator
{
    public function validate(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('title is required');
        }
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
                'price' => isset($item['price']) ? (float) $item['price'] : 0.0,
            ];
        }

        $prob = isset($input['probability']) ? (int) $input['probability'] : 10;
        if ($prob < 0 || $prob > 100) {
            throw new InvalidArgumentException('probability must be between 0 and 100');
        }

        return [
            'lead_id' => isset($input['lead_id']) ? (int) $input['lead_id'] : null,
            'customer_id' => isset($input['customer_id']) ? (int) $input['customer_id'] : null,
            'title' => $title,
            'stage' => $input['stage'] ?? 'qualification',
            'probability' => $prob,
            'expected_value' => isset($input['expected_value']) ? (float) $input['expected_value'] : 0.0,
            'closing_date' => $input['closing_date'] ?? null,
            'status' => $input['status'] ?? 'open',
            'items' => $normalizedItems,
        ];
    }
}
