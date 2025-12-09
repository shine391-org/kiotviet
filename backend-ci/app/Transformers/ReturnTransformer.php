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
        
        // Map field names for frontend compatibility
        $row['return_code'] = $row['return_number'] ?? null;
        $row['goods_amount'] = isset($row['return_amount']) ? (float) $row['return_amount'] : 0.0;
        $row['need_refund'] = isset($row['refund_amount']) ? (float) $row['refund_amount'] : 0.0;
        $row['refunded_amount'] = 0.0; // TODO: Calculate from payments when available
        
        // Keep original fields for backward compatibility
        $row['return_amount'] = isset($row['return_amount']) ? (float) $row['return_amount'] : 0.0;
        $row['refund_amount'] = isset($row['refund_amount']) ? (float) $row['refund_amount'] : null;
        $row['refund_shipping_fee'] = (bool) ($row['refund_shipping_fee'] ?? false);
        $row['lock_version'] = isset($row['lock_version']) ? (int) $row['lock_version'] : 0;
        
        // JOINed fields - pass through if present
        $row['invoice_code'] = $row['invoice_code'] ?? null;
        $row['customer_name'] = $row['customer_name'] ?? null;
        $row['branch_name'] = $row['branch_name'] ?? null;
        $row['seller_name'] = $row['seller_name'] ?? null;
        $row['return_time'] = $row['return_time'] ?? $row['created_at'] ?? null;
        $row['shipping_code'] = $row['shipping_code'] ?? null; // From shipments if available
        
        // Summary fields for list totals
        $row['receiver_name'] = $row['seller_name'] ?? null; // Same as seller for now
        $row['creator_name'] = $row['seller_name'] ?? null;

        if (isset($row['items'])) {
            $row['items'] = array_map(fn ($i) => [
                'id' => isset($i['id']) ? (int) $i['id'] : null,
                'order_item_id' => (int) ($i['order_item_id'] ?? 0),
                'quantity_returned' => (float) ($i['quantity_returned'] ?? 0),
                'condition' => $i['item_condition'] ?? $i['condition'] ?? null,
            ], $row['items']);
        }

        // Transform payment history if present
        if (isset($row['payment_history'])) {
            $row['payment_history'] = array_map(fn ($p) => [
                'id' => isset($p['id']) ? (int) $p['id'] : null,
                'receipt_code' => $p['receipt_code'] ?? null,
                'created_at' => $p['created_at'] ?? null,
                'amount' => isset($p['amount']) ? (float) $p['amount'] : 0.0,
                'payment_method' => $p['payment_method'] ?? null,
                'status' => $p['status'] ?? 'pending',
                'creator_name' => $p['creator_name'] ?? null,
                'receiver_name' => $p['receiver_name'] ?? null,
                'account_number' => $p['account_number'] ?? null,
                'notes' => $p['notes'] ?? null,
            ], $row['payment_history']);
            
            // Calculate total refunded from payment history
            $row['refunded_amount'] = array_sum(array_column($row['payment_history'], 'amount'));
        }

        return $row;
    }

    public function transformList(array $rows): array
    {
        return array_map(fn ($r) => $this->transform($r), $rows);
    }
}
