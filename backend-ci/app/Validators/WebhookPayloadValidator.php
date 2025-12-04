<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate e-commerce webhook payloads.
 *
 * @agent-validator: Ecommerce webhook payload
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class WebhookPayloadValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate product sync payload. */
    public function validateProduct(array $input): array
    {
        $data = $this->run($input, [
            'idempotency_key' => 'required|string|max_length[150]',
            'event' => 'required|string|max_length[100]',
        ]);
        $product = $input['data'] ?? null;
        if (! is_array($product)) {
            throw new InvalidArgumentException('data is required');
        }
        $product['code'] = trim((string) ($product['code'] ?? ''));
        $productData = $this->run($product, [
            'code' => 'required|string|max_length[100]',
            'name' => 'required|string|max_length[255]',
            'price' => 'permit_empty|numeric',
        ]);
        if (isset($productData['price'])) {
            $productData['price'] = (float) $productData['price'];
        }

        $data['event'] = strtolower($data['event']);
        return array_merge($data, ['data' => $productData]);
    }

    /** Validate order sync payload. */
    public function validateOrder(array $input): array
    {
        $data = $this->run($input, [
            'idempotency_key' => 'required|string|max_length[150]',
            'event' => 'required|string|max_length[100]',
        ]);

        $order = $input['data'] ?? null;
        if (! is_array($order)) {
            throw new InvalidArgumentException('data is required');
        }
        $orderData = $this->run($order, [
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'payment_method' => 'permit_empty|string|max_length[50]',
        ]);

        $items = $order['items'] ?? null;
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required');
        }
        $normalizedItems = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid item at index ' . $idx);
            }
            $prodCode = trim((string) ($item['product_code'] ?? ''));
            if ($prodCode === '') {
                throw new InvalidArgumentException('product_code required for item ' . $idx);
            }
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0 for item ' . $idx);
            }
            $price = isset($item['price']) ? (float) $item['price'] : null;
            $normalizedItems[] = [
                'product_code' => $prodCode,
                'quantity' => $qty,
                'price' => $price,
            ];
        }

        $orderData['items'] = $normalizedItems;
        $orderData['payment_method'] = $orderData['payment_method'] ?? 'CASH';
        $orderData['branch_id'] = $orderData['branch_id'] ?? 1;

        $data['event'] = strtolower($data['event']);
        return array_merge($data, ['data' => $orderData]);
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
