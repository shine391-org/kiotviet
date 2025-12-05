<?php

namespace App\Transformers;

/**
 * Shape return responses.
 *
 * @agent-transformer: Return formatter
 * @agent-pattern: Response shaping
 * @agent-reusable: MEDIUM
 */
class ReturnTransformer
{
    public function transform(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['order_id'] = isset($row['order_id']) ? (int) $row['order_id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['return_amount'] = isset($row['return_amount']) ? (float) $row['return_amount'] : 0.0;
        $row['refund_amount'] = isset($row['refund_amount']) ? (float) $row['refund_amount'] : null;
        $row['refund_shipping_fee'] = (bool) ($row['refund_shipping_fee'] ?? false);
        $row['lock_version'] = isset($row['lock_version']) ? (int) $row['lock_version'] : 0;

        if (isset($row['items'])) {
            $row['items'] = array_map(fn ($i) => [
                'id' => isset($i['id']) ? (int) $i['id'] : null,
                'order_item_id' => (int) ($i['order_item_id'] ?? 0),
                'quantity_returned' => (float) ($i['quantity_returned'] ?? 0),
                'condition' => $i['item_condition'] ?? $i['condition'] ?? null,
            ], $row['items']);
        }

        return $row;
    }

    public function transformList(array $rows): array
    {
        return array_map(fn ($r) => $this->transform($r), $rows);
    }
}
