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

        // FE-compatible field aliases
        $row['invoice_code'] = $row['invoice_number'] ?? null;
        $row['issued_at'] = $row['issue_date'] ?? null;
        $row['creator_name'] = $row['created_by_name'] ?? null;
        $row['branch_name'] = $row['branch_name'] ?? null;
        $row['note'] = $row['notes'] ?? null;

        $row['customer_name'] = $row['customer_name'] ?? null;
        $row['created_by_name'] = $row['created_by_name'] ?? null;
        $row['invoice_status'] = $row['invoice_status'] ?? null;
        $row['invoice_type'] = $row['invoice_type'] ?? null;
        $row['e_invoice_status'] = $row['e_invoice_status'] ?? null;
        $row['payment_status'] = $row['payment_status'] ?? null;

        // Delivery fields
        $row['delivery_status'] = $row['delivery_status'] ?? null;
        $row['shipment_code'] = $row['shipment_code'] ?? null;
        $row['shipping_partner'] = $row['shipping_partner'] ?? null;
        $row['delivery_time'] = $row['delivery_time'] ?? null;
        $row['delivery_note'] = $row['delivery_note'] ?? null;

        // Customer fields
        $row['customer_code'] = $row['customer_code'] ?? null;
        $row['phone'] = $row['phone'] ?? null;
        $row['email'] = $row['email'] ?? null;
        $row['address'] = $row['address'] ?? null;
        $row['region'] = $row['region'] ?? null;
        $row['ward'] = $row['ward'] ?? null;

        // Other fields
        $row['order_code'] = $row['order_code'] ?? null;
        $row['return_code'] = $row['return_code'] ?? null;
        $row['reconciliation_code'] = $row['reconciliation_code'] ?? null;
        $row['seller_name'] = $row['seller_name'] ?? null;
        $row['sales_channel'] = $row['sales_channel'] ?? null;

        // Numeric fields
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0.0;
        $row['vat_rate'] = isset($row['vat_rate']) ? (float) $row['vat_rate'] : 0.0;
        $row['vat_amount'] = isset($row['vat_amount']) ? (float) $row['vat_amount'] : 0.0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        $row['goods_total'] = isset($row['goods_total']) ? (float) $row['goods_total'] : 0.0;
        $row['discount_total'] = isset($row['discount_total']) ? (float) $row['discount_total'] : 0.0;
        $row['net_total'] = isset($row['net_total']) ? (float) $row['net_total'] : 0.0;
        $row['tax_amount'] = isset($row['tax_amount']) ? (float) $row['tax_amount'] : 0.0;
        $row['tax_discount'] = isset($row['tax_discount']) ? (float) $row['tax_discount'] : 0.0;
        $row['other_fee'] = isset($row['other_fee']) ? (float) $row['other_fee'] : 0.0;
        $row['shipping_fee'] = isset($row['shipping_fee']) ? (float) $row['shipping_fee'] : 0.0;
        $row['customer_payable'] = isset($row['customer_payable']) ? (float) $row['customer_payable'] : 0.0;
        $row['customer_paid'] = isset($row['customer_paid']) ? (float) $row['customer_paid'] : 0.0;
        $row['cod_amount'] = isset($row['cod_amount']) ? (float) $row['cod_amount'] : 0.0;
        $row['rounding_adjustment'] = isset($row['rounding_adjustment']) ? (float) $row['rounding_adjustment'] : 0.0;
        $row['total_paid'] = isset($row['total_paid']) ? (float) $row['total_paid'] : 0.0;
        $row['payment_discount'] = isset($row['payment_discount']) ? (float) $row['payment_discount'] : 0.0;
        $row['last_payment_date'] = $row['last_payment_date'] ?? null;

        if (isset($row['orders'])) {
            $row['orders'] = array_map(fn ($o) => [
                'order_id' => (int) ($o['order_id'] ?? $o['id'] ?? 0),
                'total' => isset($o['total']) ? (float) $o['total'] : 0.0,
            ], $row['orders']);
        }

        // Transform items (product lines)
        if (isset($row['items'])) {
            $row['items'] = array_map(fn ($item) => [
                'id' => (int) ($item['id'] ?? 0),
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_code' => $item['product_code'] ?? $item['sku'] ?? null,
                'product_name' => $item['product_name'] ?? $item['name'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'discount' => (float) ($item['discount'] ?? 0),
                'final_price' => (float) ($item['final_price'] ?? $item['unit_price'] ?? 0),
            ], $row['items']);
        }

        // Transform payments
        if (isset($row['payments'])) {
            $row['payments'] = array_map(fn ($p) => [
                'id' => (int) ($p['id'] ?? 0),
                'code' => $p['code'] ?? $p['ref_code'] ?? 'TTH' . str_pad($p['id'] ?? 0, 6, '0', STR_PAD_LEFT),
                'created_at' => $p['created_at'] ?? $p['paid_at'] ?? null,
                'creator_name' => $p['creator_name'] ?? 'Lano',
                'amount' => (float) ($p['amount'] ?? 0),
                'payment_method' => $p['payment_method'] ?? $p['method'] ?? 'Tiền mặt',
                'status' => $p['status'] ?? 'Đã thanh toán',
                'cash_amount' => (float) ($p['amount'] ?? 0),
            ], $row['payments']);
        }

        // Transform delivery history
        if (isset($row['delivery_history'])) {
            $row['delivery_history'] = array_map(fn ($d) => [
                'id' => (int) ($d['id'] ?? 0),
                'time' => $d['created_at'] ?? $d['time'] ?? null,
                'location' => $d['location'] ?? null,
                'message' => $d['message'] ?? $d['note'] ?? null,
            ], $row['delivery_history']);
        }

        return $row;
    }

    public function transformList(array $rows): array
    {
        return array_map(fn ($r) => $this->transform($r), $rows);
    }
}
