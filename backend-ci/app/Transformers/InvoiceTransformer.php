<?php

namespace App\Transformers;

/**
 * Shape invoice responses.
 *
 * @agent-transformer: Invoice formatter
 * @agent-pattern: Response shaping
 * @agent-reusable: MEDIUM
 */
class InvoiceTransformer
{
    public function transform(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0.0;
        $row['vat_rate'] = isset($row['vat_rate']) ? (float) $row['vat_rate'] : 0.0;
        $row['vat_amount'] = isset($row['vat_amount']) ? (float) $row['vat_amount'] : 0.0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        if (isset($row['orders'])) {
            $row['orders'] = array_map(fn ($o) => [
                'order_id' => (int) ($o['order_id'] ?? $o['id'] ?? 0),
                'total' => isset($o['total']) ? (float) $o['total'] : 0.0,
            ], $row['orders']);
        }
        return $row;
    }

    public function transformList(array $rows): array
    {
        return array_map(fn ($r) => $this->transform($r), $rows);
    }
}
