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
        $row['customer_name'] = $row['customer_name'] ?? null;
        $row['created_by_name'] = $row['created_by_name'] ?? null;
        $row['invoice_status'] = $row['invoice_status'] ?? null;
        $row['invoice_type'] = $row['invoice_type'] ?? null;
        $row['e_invoice_status'] = $row['e_invoice_status'] ?? null;
        $row['payment_status'] = $row['payment_status'] ?? null;
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0.0;
        $row['vat_rate'] = isset($row['vat_rate']) ? (float) $row['vat_rate'] : 0.0;
        $row['vat_amount'] = isset($row['vat_amount']) ? (float) $row['vat_amount'] : 0.0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        $row['goods_total'] = isset($row['goods_total']) ? (float) $row['goods_total'] : 0.0;
        $row['discount_total'] = isset($row['discount_total']) ? (float) $row['discount_total'] : 0.0;
        $row['net_total'] = isset($row['net_total']) ? (float) $row['net_total'] : 0.0;
        $row['tax_amount'] = isset($row['tax_amount']) ? (float) $row['tax_amount'] : 0.0;
        $row['other_fee'] = isset($row['other_fee']) ? (float) $row['other_fee'] : 0.0;
        $row['shipping_fee'] = isset($row['shipping_fee']) ? (float) $row['shipping_fee'] : 0.0;
        $row['customer_payable'] = isset($row['customer_payable']) ? (float) $row['customer_payable'] : 0.0;
        $row['customer_paid'] = isset($row['customer_paid']) ? (float) $row['customer_paid'] : 0.0;
        $row['cod_amount'] = isset($row['cod_amount']) ? (float) $row['cod_amount'] : 0.0;
        $row['rounding_adjustment'] = isset($row['rounding_adjustment']) ? (float) $row['rounding_adjustment'] : 0.0;
        $row['total_paid'] = isset($row['total_paid']) ? (float) $row['total_paid'] : 0.0;
        $row['last_payment_date'] = $row['last_payment_date'] ?? null;
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
