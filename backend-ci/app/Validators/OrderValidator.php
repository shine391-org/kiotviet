<?php

namespace App\Validators;

use InvalidArgumentException;

/** Order payload validation. @agent-validator: Order @agent-pattern: Lightweight validation @agent-reusable: MEDIUM */
class OrderValidator
{
    /** Validate order/create or preview payload. */
    public function validateOrder(array $input): array
    {
        $items = $input['items'] ?? null;
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required');
        }

        $orderDate = $input['order_date'] ?? date('Y-m-d');
        if ($orderDate && ! $this->validDate($orderDate)) {
            throw new InvalidArgumentException('order_date is invalid');
        }

        $mapped = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) { throw new InvalidArgumentException('product_id is required'); }
            $qty = (float) ($item['quantity'] ?? 1);
            if ($qty <= 0) { throw new InvalidArgumentException('quantity must be > 0'); }
            $mapped[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => $qty,
            ];
        }

        return [
            'customer_id' => isset($input['customer_id']) ? (int) $input['customer_id'] : null,
            'customer_group_id' => isset($input['customer_group_id']) ? (int) $input['customer_group_id'] : null,
            'order_date' => $orderDate,
            'items' => $mapped,
        ];
    }

    private function validDate(string $date): bool
    {
        $d = date_create_from_format('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
