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
            $serials = $this->serialNumbersFromInput($item['serial_numbers'] ?? []);
            if (! empty($serials) && count($serials) !== (int) $qty) {
                throw new InvalidArgumentException('serial_numbers count must match quantity');
            }
            $mapped[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'batch_id' => isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                'quantity' => $qty,
                'serial_numbers' => $serials,
            ];
        }

        $priceListId = isset($input['price_list_id']) ? (int) $input['price_list_id'] : null;
        if ($priceListId !== null && $priceListId <= 0) {
            throw new InvalidArgumentException('price_list_id must be positive');
        }

        return [
            'customer_id' => isset($input['customer_id']) ? (int) $input['customer_id'] : null,
            'customer_group_id' => isset($input['customer_group_id']) ? (int) $input['customer_group_id'] : null,
            'order_date' => $orderDate,
            'price_list_id' => $priceListId,
            'items' => $mapped,
        ];
    }

    private function validDate(string $date): bool
    {
        $d = date_create_from_format('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /** Normalize serial numbers from mixed input. */
    private function serialNumbersFromInput($value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }
        if (! is_array($value)) {
            return [];
        }
        $serials = array_values(array_filter(array_map(static function ($serial) {
            if (is_numeric($serial)) {
                return (string) $serial;
            }
            if (is_string($serial)) {
                return trim($serial);
            }
            return null;
        }, $value), static fn ($v) => $v !== null && $v !== ''));

        return array_values(array_unique($serials));
    }
}
