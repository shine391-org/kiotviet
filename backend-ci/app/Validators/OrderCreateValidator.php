<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate order create payload.
 *
 * @agent-validator: Order create
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class OrderCreateValidator
{
    public function validate(array $input): array
    {
        $items = $input['items'] ?? null;
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $orderType = $input['order_type'] ?? 'pos';
        if (! in_array($orderType, ['pos', 'shipping'], true)) {
            throw new InvalidArgumentException('order_type must be pos or shipping');
        }

        $paymentMethod = $input['payment_method'] ?? null;
        if (! $paymentMethod) {
            throw new InvalidArgumentException('payment_method is required');
        }

        $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : null;
        if ($customerId !== null && $customerId < 0) {
            throw new InvalidArgumentException('customer_id invalid');
        }

        $shippingFee = isset($input['shipping_fee']) ? (float) $input['shipping_fee'] : 0.0;
        if ($shippingFee < 0) {
            throw new InvalidArgumentException('shipping_fee must be >= 0');
        }

        $paidAmount = isset($input['paid_amount']) ? (float) $input['paid_amount'] : 0.0;
        if ($paidAmount < 0) {
            throw new InvalidArgumentException('paid_amount must be >= 0');
        }

        $orderItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0');
            }
            $orderItems[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => $qty,
            ];
        }

        return [
            'customer_id' => $customerId,
            'customer_group_id' => isset($input['customer_group_id']) ? (int) $input['customer_group_id'] : null,
            'order_type' => $orderType,
            'payment_method' => trim((string) $paymentMethod),
            'order_date' => $input['order_date'] ?? date('Y-m-d'),
            'shipping_fee' => $shippingFee,
            'paid_amount' => $paidAmount,
            'notes' => isset($input['notes']) ? trim((string) $input['notes']) : null,
            'shipping' => [
                'name' => $input['shipping_name'] ?? ($input['shipping']['name'] ?? null),
                'phone' => $input['shipping_phone'] ?? ($input['shipping']['phone'] ?? null),
                'address' => $input['shipping_address'] ?? ($input['shipping']['address'] ?? null),
                'ward' => $input['shipping_ward'] ?? ($input['shipping']['ward'] ?? null),
                'district' => $input['shipping_district'] ?? ($input['shipping']['district'] ?? null),
                'city' => $input['shipping_city'] ?? ($input['shipping']['city'] ?? null),
            ],
            'items' => $orderItems,
        ];
    }
}
