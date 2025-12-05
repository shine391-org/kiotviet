<?php

namespace App\Services\Ecommerce;

use App\Repositories\Ecommerce\EcommerceWebhookLogRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Orders\OrderService;
use App\Validators\WebhookPayloadValidator;
use InvalidArgumentException;

/**
 * Handle incoming e-commerce webhooks.
 *
 * @agent-service: Ecommerce integration
 * @agent-pattern: Idempotent webhook handler
 * @agent-reusable: MEDIUM
 */
class EcommerceIntegrationService
{
    protected ProductRepository $products;
    protected OrderService $orders;
    protected EcommerceWebhookLogRepository $logs;
    protected WebhookPayloadValidator $validator;

    public function __construct(
        ?ProductRepository $products = null,
        ?OrderService $orders = null,
        ?EcommerceWebhookLogRepository $logs = null,
        ?WebhookPayloadValidator $validator = null
    ) {
        $this->products = $products ?? new ProductRepository();
        $this->orders = $orders ?? new OrderService();
        $this->logs = $logs ?? new EcommerceWebhookLogRepository();
        $this->validator = $validator ?? new WebhookPayloadValidator();
    }

    /** Product sync webhook. */
    public function handleProductWebhook(array $payload): array
    {
        $validated = $this->validator->validateProduct($payload);
        $duplicate = $this->checkDuplicate($validated['idempotency_key']);
        if ($duplicate) {
            return ['success' => true, 'duplicate' => true, 'data' => $duplicate];
        }

        $product = $this->products->findByCode($validated['data']['code']);
        if ($product) {
            $this->products->update((int) $product['id'], [
                'name' => $validated['data']['name'],
                'selling_price' => $validated['data']['price'] ?? ($product['selling_price'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $productId = (int) $product['id'];
        } else {
            $created = $this->products->create([
                'code' => $validated['data']['code'],
                'name' => $validated['data']['name'],
                'selling_price' => $validated['data']['price'] ?? 0,
                'status' => 'active',
            ]);
            $productId = (int) $created['id'];
        }

        $log = $this->logs->log('ecommerce', $validated['event'], $validated['idempotency_key'], $this->hashPayload($payload));
        return ['success' => true, 'data' => ['product_id' => $productId, 'log_id' => $log['id']], 'duplicate' => false];
    }

    /** Order sync webhook. */
    public function handleOrderWebhook(array $payload): array
    {
        $validated = $this->validator->validateOrder($payload);
        $duplicate = $this->checkDuplicate($validated['idempotency_key']);
        if ($duplicate) {
            return ['success' => true, 'duplicate' => true, 'data' => $duplicate];
        }

        $items = [];
        foreach ($validated['data']['items'] as $item) {
            $product = $this->products->findByCode($item['product_code']);
            if (! $product) {
                $created = $this->products->create([
                    'code' => $item['product_code'],
                    'name' => $item['product_code'],
                    'selling_price' => $item['price'] ?? 0,
                    'status' => 'active',
                ]);
                $productId = (int) $created['id'];
            } else {
                $productId = (int) $product['id'];
            }
            $items[] = [
                'product_id' => $productId,
                'variant_id' => null,
                'quantity' => $item['quantity'],
            ];
        }

        $orderPayload = [
            'customer_id' => null,
            'order_type' => 'shipping',
            'payment_method' => $validated['data']['payment_method'],
            'order_date' => date('Y-m-d'),
            'branch_id' => (int) $validated['data']['branch_id'],
            'shipping_fee' => 0,
            'paid_amount' => 0,
            'notes' => null,
            'shipping_name' => 'Ecom',
            'shipping_phone' => 'N/A',
            'shipping_address' => 'Ecommerce',
            'items' => $items,
        ];

        $order = $this->orders->create($orderPayload);

        $log = $this->logs->log('ecommerce', $validated['event'], $validated['idempotency_key'], $this->hashPayload($payload));
        return ['success' => true, 'data' => ['order_id' => $order['data']['id'] ?? null, 'log_id' => $log['id']], 'duplicate' => false];
    }

    private function checkDuplicate(string $key): ?array
    {
        $existing = $this->logs->findByKey($key);
        return $existing ? ['log_id' => $existing['id'], 'status' => $existing['status']] : null;
    }

    private function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload));
    }
}
