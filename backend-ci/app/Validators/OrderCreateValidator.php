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

        $branchId = isset($input['branch_id']) ? (int) $input['branch_id'] : 0;
        if ($branchId <= 0) {
            throw new InvalidArgumentException('branch_id is required');
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

        $priceListId = isset($input['price_list_id']) ? (int) $input['price_list_id'] : null;
        if ($priceListId !== null && $priceListId <= 0) {
            throw new InvalidArgumentException('price_list_id must be positive');
        }
        $posProfileId = isset($input['pos_profile_id']) ? (int) $input['pos_profile_id'] : null;
        if ($posProfileId !== null && $posProfileId <= 0) {
            throw new InvalidArgumentException('pos_profile_id must be positive');
        }
        $redeemPoints = isset($input['redeem_points']) ? (int) $input['redeem_points'] : 0;
        if ($redeemPoints < 0) {
            throw new InvalidArgumentException('redeem_points must be >= 0');
        }
        $couponCode = isset($input['coupon_code']) ? trim((string) $input['coupon_code']) : null;
        if ($couponCode !== null && $couponCode === '') {
            $couponCode = null;
        }
        if ($redeemPoints > 0 && ($customerId === null || $customerId <= 0)) {
            throw new InvalidArgumentException('customer_id is required to redeem points');
        }

        $payments = $input['payments'] ?? null;
        $paymentMethod = $input['payment_method'] ?? null;
        $paymentsArr = [];
        if ($payments && is_array($payments)) {
            foreach ($payments as $p) {
                $pm = isset($p['payment_method']) ? strtoupper(trim((string) $p['payment_method'])) : null;
                $amt = isset($p['amount']) ? (float) $p['amount'] : 0;
                if (! $pm) {
                    throw new InvalidArgumentException('payment_method in payments is required');
                }
                if ($amt <= 0) {
                    throw new InvalidArgumentException('payment amount must be > 0');
                }
                $paymentsArr[] = [
                    'payment_method' => $pm,
                    'amount' => $amt,
                ];
            }
            $paidAmount = array_sum(array_column($paymentsArr, 'amount'));
        }
        $paymentMethod = $paymentMethod ? strtoupper(trim((string) $paymentMethod)) : null;
        if (! $paymentMethod && empty($paymentsArr)) {
            throw new InvalidArgumentException('payment_method is required when payments not provided');
        }

        $createdBy = isset($input['created_by']) ? (int) $input['created_by'] : null;
        if (! $createdBy && isset($input['user_id'])) {
            $createdBy = (int) $input['user_id'];
        }
        if ($createdBy !== null && $createdBy <= 0) {
            throw new InvalidArgumentException('created_by must be positive');
        }

        if ($orderType === 'pos' && empty($paymentsArr) && $paymentMethod) {
            if ($paidAmount <= 0) {
                throw new InvalidArgumentException('paid_amount must be > 0 for POS payments');
            }
            $paymentsArr[] = [
                'payment_method' => $paymentMethod,
                'amount' => $paidAmount,
            ];
        }
        if ($orderType === 'pos' && empty($paymentsArr)) {
            throw new InvalidArgumentException('payments are required for POS orders');
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
            $serials = $this->serialNumbersFromInput($item['serial_numbers'] ?? []);
            if (! empty($serials) && count($serials) !== (int) $qty) {
                throw new InvalidArgumentException('serial_numbers count must match quantity');
            }
            $orderItems[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'batch_id' => isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                'quantity' => $qty,
                'serial_numbers' => $serials,
            ];
        }

        return [
            'customer_id' => $customerId,
            'customer_group_id' => isset($input['customer_group_id']) ? (int) $input['customer_group_id'] : null,
            'order_type' => $orderType,
            'payment_method' => trim((string) $paymentMethod),
            'order_date' => $input['order_date'] ?? date('Y-m-d'),
            'branch_id' => $branchId,
            'price_list_id' => $priceListId,
            'pos_profile_id' => $posProfileId,
            'user_id' => $createdBy,
            'created_by' => $createdBy,
            'shipping_fee' => $shippingFee,
            'paid_amount' => $paidAmount,
            'payments' => $paymentsArr ?: null,
            'redeem_points' => $redeemPoints,
            'coupon_code' => $couponCode ? strtoupper($couponCode) : null,
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
